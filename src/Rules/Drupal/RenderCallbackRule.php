<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use Drupal\Core\Render\PlaceholderGeneratorInterface;
use Drupal\Core\Render\RendererInterface;

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

        // @todo this should be 3 rules.
        // @see https://www.drupal.org/node/2966725
        $keysToCheck = ['#pre_render', '#post_render', '#access_callback', '#lazy_builder'];
        $keySearch = array_search($key->value, $keysToCheck, true);
        if ($keySearch === false) {
            return [];
        }
        $keyChecked = $keysToCheck[$keySearch];

        $value = $node->value;

        $errors = [];

        // @todo Move into its own rule.
        if ($keyChecked === '#lazy_builder') {
            // Check if being used in array_intersect_key.
            // NOTE: This only works against existing patterns in Drupal core where the array with boolean values is
            // being passed as the argument to array_intersect_key.
            $parent = $node->getAttribute('parent');
            if ($parent instanceof Node\Expr\Array_) {
                $parent = $parent->getAttribute('parent');
                if ($parent instanceof Node\Arg) {
                    $parent = $parent->getAttribute('parent');
                    if ($parent instanceof Node\Expr\FuncCall
                        && $parent->name instanceof Name
                        && $parent->name->toString() === 'array_intersect_key'
                    ) {
                        return [];
                    }
                }
            }

            if (!$value instanceof Node\Expr\Array_) {
                return [
                    RuleErrorBuilder::message(sprintf('The "%s" expects a callable array with arguments.', $keyChecked))
                        ->line($node->getLine())->build()
                ];
            }
            if (count($value->items) === 0) {
                return [];
            }
            if ($value->items[0] === null) {
                return [];
            }
            // @todo take $value->items[1] and validate parameters against the callback.
            $errors[] = $this->doProcessNode($value->items[0]->value, $scope, $keyChecked, 0);
        } elseif ($keyChecked === '#access_callback') {
            // @todo move into its own rule.
            $errors[] = $this->doProcessNode($value, $scope, $keyChecked, 0);
        } else {
            // @todo keep here.
            if (!$value instanceof Node\Expr\Array_) {
                return [
                    RuleErrorBuilder::message(sprintf('The "%s" render array value expects an array of callbacks.', $keyChecked))
                        ->line($node->getLine())->build()
                ];
            }
            if (count($value->items) === 0) {
                return [];
            }
            foreach ($value->items as $pos => $item) {
                if (!$item instanceof Node\Expr\ArrayItem) {
                    continue;
                }
                $errors[] = $this->doProcessNode($item->value, $scope, $keyChecked, $pos);
            }
        }
        return array_filter($errors);
    }

    private function doProcessNode(Node\Expr $node, Scope $scope, string $keyChecked, int $pos): ?RuleError
    {
        $trustedCallbackType = new UnionType([
            new ObjectType('Drupal\Core\Security\TrustedCallbackInterface'),
            new ObjectType('Drupal\Core\Render\Element\RenderCallbackInterface'),
        ]);

        $errorLine = $node->getLine();
        $type = $this->getType($node, $scope);

        if ($type instanceof ConstantStringType) {
            if (!$type->isCallable()->yes()) {
                return RuleErrorBuilder::message(
                    sprintf("%s callback %s at key '%s' is not callable.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                )->line($errorLine)->build();
            }
            // We can determine if the callback is callable through the type system. However, we cannot determine
            // if it is just a function or a static class call (MyClass::staticFunc).
            if ($this->reflectionProvider->hasFunction(new Name($type->getValue()), null)) {
                return RuleErrorBuilder::message(
                    sprintf("%s callback %s at key '%s' is not trusted.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                )->line($errorLine)
                    ->tip('Change record: https://www.drupal.org/node/2966725.')
                    ->build();
            }
        } elseif ($type instanceof ConstantArrayType) {
            if (!$type->isCallable()->yes()) {
                return RuleErrorBuilder::message(
                    sprintf("%s callback %s at key '%s' is not callable.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                )->line($errorLine)->build();
            }
            $typeAndMethodName = $type->findTypeAndMethodName();
            if ($typeAndMethodName === null) {
                throw new \PHPStan\ShouldNotHappenException();
            }

            if (!$trustedCallbackType->isSuperTypeOf($typeAndMethodName->getType())->yes()) {
                return RuleErrorBuilder::message(
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
                    return RuleErrorBuilder::message(
                        sprintf("%s may not contain a closure at key '%s' as forms may be serialized and serialization of closures is not allowed.", $keyChecked, $pos)
                    )->line($errorLine)->build();
                }
            }
        } elseif ($type instanceof ThisType) {
            if (!$type->isCallable()->yes()) {
                return RuleErrorBuilder::message(
                    sprintf("%s callback %s at key '%s' is not callable.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                )->line($errorLine)->build();
            }
        } elseif ($type instanceof IntersectionType) {
            // Try to provide a tip for this weird occurrence.
            $tip = '';
            if ($node instanceof Node\Expr\BinaryOp\Concat) {
                $leftStringType = $scope->getType($node->left)->toString();
                $rightStringType = $scope->getType($node->right)->toString();
                if ($leftStringType instanceof GenericClassStringType && $rightStringType instanceof ConstantStringType) {
                    $methodName = str_replace(':', '', $rightStringType->getValue());
                    $tip = "Refactor concatenation of `static::class` with method name to an array callback: [static::class, '$methodName']";
                }
            }

            if ($tip === '') {
                $tip = 'If this error is unexpected, open an issue with the error and sample code https://github.com/mglaman/phpstan-drupal/issues/new';
            }

            return RuleErrorBuilder::message(
                sprintf("%s value '%s' at key '%s' is invalid.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
            )->line($errorLine)->tip($tip)->build();
        } else {
            return RuleErrorBuilder::message(
                sprintf("%s value '%s' at key '%s' is invalid.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
            )->line($errorLine)->build();
        }

        return null;
    }

    // @todo move to a helper, as Drupal uses `service:method` references a lot.
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
