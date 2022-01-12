<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

final class RenderCallbackRule implements Rule
{

    protected ReflectionProvider $reflectionProvider;

    protected ServiceMap $serviceMap;

    public function __construct(ReflectionProvider $reflectionProvider, ServiceMap $serviceMap)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->serviceMap = $serviceMap;
    }

    public function getNodeType(): string
    {
        return Node\Expr\ArrayItem::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\ArrayItem);
        $key = $node->key;
        if (!$key instanceof Node\Scalar\String_) {
            return [];
        }
        // @see https://www.drupal.org/node/2966725

        $keysToCheck = ['#pre_render', '#post_render', '#access_callback', '#lazy_builder'];
        $keySearch = array_search($key->value, $keysToCheck, true);
        if ($keySearch === false) {
            return [];
        }
        $keyChecked = $keysToCheck[$keySearch];

        $value = $node->value;
        if (!$value instanceof Node\Expr\Array_) {
            return [
                RuleErrorBuilder::message(sprintf('The "%s" render array value expects an array of callbacks.', $keyChecked))
                    ->line($node->getLine())->build()
            ];
        }
        if (count($value->items) === 0) {
            return [];
        }

        $trustedCallbackType = new UnionType([
            new ObjectType('Drupal\Core\Security\TrustedCallbackInterface'),
            new ObjectType('Drupal\Core\Render\Element\RenderCallbackInterface'),
        ]);
        $errors = [];
        foreach ($value->items as $pos => $item) {
            if (!$item instanceof Node\Expr\ArrayItem) {
                continue;
            }
            // '#lazy_builder' has two items, callback and args. Others are direct callbacks.
            // Lazy builder in Renderer: $elements['#lazy_builder'][0], $elements['#lazy_builder'][1]
            if ($keyChecked === '#lazy_builder') {
                if (!$item->value instanceof Node\Expr\Array_) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf("%s callback %s at key '%s' is not callable.", $keyChecked, $scope->getType($item->value)->describe(VerbosityLevel::value()), $pos)
                    )->line($item->value->getLine())->build();
                    continue;
                }

                if (count($item->value->items) !== 2) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf("%s callback %s at key '%s' is not valid. First value must be a callback and second value its arguments.", $keyChecked, $scope->getType($item->value)->describe(VerbosityLevel::value()), $pos)
                    )->line($item->value->getLine())->build();
                    continue;
                }
                // Replace $item with our nested callback.
                $item = $item->value->items[0];
            }
            $errorLine = $item->value->getLine();
            $type = $this->getType($item->value, $scope);

            if ($type instanceof ConstantStringType) {
                if (!$type->isCallable()->yes()) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf("%s callback %s at key '%s' is not callable.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                    )->line($errorLine)->build();
                    continue;
                }
                // We can determine if the callback is callable through the type system. However, we cannot determine
                // if it is just a function or a static class call (MyClass::staticFunc).
                if ($this->reflectionProvider->hasFunction(new \PhpParser\Node\Name($type->getValue()), null)) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf("%s callback %s at key '%s' is not trusted.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                    )->line($errorLine)
                        ->tip('Change record: https://www.drupal.org/node/2966725.')
                        ->build();
                }
            } elseif ($type instanceof ConstantArrayType) {
                if (!$type->isCallable()->yes()) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf("%s callback %s at key '%s' is not callable.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                    )->line($errorLine)->build();
                    continue;
                }
                $typeAndMethodName = $type->findTypeAndMethodName();
                if ($typeAndMethodName === null) {
                    throw new \PHPStan\ShouldNotHappenException();
                }

                if (!$trustedCallbackType->isSuperTypeOf($typeAndMethodName->getType())->yes()) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf("%s callback class '%s' at key '%s' does not implement Drupal\Core\Security\TrustedCallbackInterface.", $keyChecked, $typeAndMethodName->getType()->describe(VerbosityLevel::value()), $pos)
                    )->line($errorLine)->tip('Change record: https://www.drupal.org/node/2966725.')->build();
                }
            } elseif ($type instanceof ClosureType) {
                if ($scope->isInClass()) {
                    $classReflection = $scope->getClassReflection();
                    if ($classReflection === null) {
                        throw new \PHPStan\ShouldNotHappenException();
                    }
                    $classType = new ObjectType($classReflection->getName());
                    $formType = new ObjectType('\Drupal\Core\Form\FormInterface');
                    if ($formType->isSuperTypeOf($classType)->yes()) {
                        $errors[] = RuleErrorBuilder::message(
                            sprintf("%s may not contain a closure at key '%s' as forms may be serialized and serialization of closures is not allowed.", $keyChecked, $pos)
                        )->line($errorLine)->build();
                    }
                }
            } elseif ($type instanceof IntersectionType) {
                // Try to provide a tip for this weird occurrence.
                $tip = '';
                if ($item->value instanceof Node\Expr\BinaryOp\Concat) {
                    $leftStringType = $scope->getType($item->value->left)->toString();
                    $rightStringType = $scope->getType($item->value->right)->toString();
                    if ($leftStringType instanceof GenericClassStringType && $rightStringType instanceof ConstantStringType) {
                        $methodName = str_replace(':', '', $rightStringType->getValue());
                        $tip = "Refactor concatenation of `static::class` with method name to an array callback: [static::class, '$methodName']";
                    }
                }

                if ($tip === '') {
                    $tip = 'If this error is unexpected, open an issue with the error and sample code https://github.com/mglaman/phpstan-drupal/issues/new';
                }

                $errors[] = RuleErrorBuilder::message(
                    sprintf("%s value '%s' at key '%s' is invalid.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                )->line($errorLine)->tip($tip)->build();
            } else {
                $errors[] = RuleErrorBuilder::message(
                    sprintf("%s value '%s' at key '%s' is invalid.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                )->line($errorLine)->build();
            }
        }

        return $errors;
    }

    private function getType(Node\Expr $node, Scope $scope):  Type
    {
        $type = $scope->getType($node);
        if ($type instanceof ConstantStringType) {
            if ($type->isClassString()) {
                return $type;
            }
            // Covers  \Drupal\Core\Controller\ControllerResolver::createController.
            if (substr_count($type->getValue(), ':') === 1) {
                [$class_or_service, $method] = explode(':', $type->getValue(), 2);

                $serviceDefinition = $this->serviceMap->getService($class_or_service);
                if ($serviceDefinition === null || $serviceDefinition->getClass() === null) {
                    return $type;
                }
                return new ConstantArrayType(
                    [new ConstantIntegerType(0), new ConstantIntegerType(1)],
                    [
                        new ConstantStringType($serviceDefinition->getClass(), true),
                        new ConstantStringType($method)
                    ]
                );
            }
            // @see \PHPStan\Type\Constant\ConstantStringType::isCallable
            preg_match('#^([a-zA-Z_\\x7f-\\xff\\\\][a-zA-Z0-9_\\x7f-\\xff\\\\]*)::([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)\\z#', $type->getValue(), $matches);
            if ($matches !== null && count($matches) > 0) {
                return new ConstantArrayType(
                    [new ConstantIntegerType(0), new ConstantIntegerType(1)],
                    [
                        new ConstantStringType($matches[1], true),
                        new ConstantStringType($matches[2])
                    ]
                );
            }
        }
        return $type;
    }
}
