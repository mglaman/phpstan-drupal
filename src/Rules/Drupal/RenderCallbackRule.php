<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Render\PlaceholderGenerator;
use Drupal\Core\Render\Renderer;
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
use PHPStan\Type\StaticType;
use PHPStan\Type\ThisType;
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
            if ($scope->isInClass()) {
                $classReflection = $scope->getClassReflection();
                $classType = new ObjectType($classReflection->getName());
                // These classes use #lazy_builder in array_intersect_key. With
                // PHPStan 1.6, nodes do not track their parent/next/prev which
                // saves a lot of memory. But makes it harder to detect if we're
                // in a call to array_intersect_key. This is an easier workaround.
                $allowedTypes = [
                    PlaceholderGenerator::class,
                    Renderer::class,
                    'Drupal\Tests\Core\Render\RendererPlaceholdersTest',
                ];
                foreach ($allowedTypes as $allowedType) {
                    if ($classType->isInstanceOf($allowedType)->yes()) {
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

            if (!$trustedCallbackType->isSuperTypeOf($type)->yes()) {
                return RuleErrorBuilder::message(
                    sprintf("%s callback class %s at key '%s' does not implement Drupal\Core\Security\TrustedCallbackInterface.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                )->line($errorLine)->tip('Change record: https://www.drupal.org/node/2966725.')->build();
            }
        } elseif ($type instanceof ConstantArrayType) {
            if (!$type->isCallable()->yes()) {
                return RuleErrorBuilder::message(
                    sprintf("%s callback %s at key '%s' is not callable.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                )->line($errorLine)->build();
            }
            $typeAndMethodNames = $type->findTypeAndMethodNames();
            if ($typeAndMethodNames === []) {
                throw new \PHPStan\ShouldNotHappenException();
            }

            foreach ($typeAndMethodNames as $typeAndMethodName) {
                if (!$trustedCallbackType->isSuperTypeOf($typeAndMethodName->getType())->yes()) {
                    return RuleErrorBuilder::message(
                        sprintf("%s callback class '%s' at key '%s' does not implement Drupal\Core\Security\TrustedCallbackInterface.", $keyChecked, $typeAndMethodName->getType()->describe(VerbosityLevel::value()), $pos)
                    )->line($errorLine)->tip('Change record: https://www.drupal.org/node/2966725.')->build();
                }
            }
        } elseif ($type instanceof ClosureType) {
            if ($scope->isInClass()) {
                $classReflection = $scope->getClassReflection();
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
        } elseif ($type->isCallable()->yes()) {
            // If the value has been marked as callable or callable-string, we cannot resolve the callable, trust it.
            return null;
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
        if ($type instanceof IntersectionType) {
            // Covers concatenation of static::class . '::methodName'.
            if ($node instanceof Node\Expr\BinaryOp\Concat) {
                $leftType = $scope->getType($node->left);
                $rightType = $scope->getType($node->right);
                if ($rightType instanceof ConstantStringType && $leftType instanceof GenericClassStringType && $leftType->getGenericType() instanceof StaticType) {
                    return new ConstantArrayType(
                        [new ConstantIntegerType(0), new ConstantIntegerType(1)],
                        [
                            $leftType->getGenericType(),
                            new ConstantStringType(ltrim($rightType->getValue(), ':'))
                        ]
                    );
                }
            }
        } elseif ($type instanceof ConstantStringType) {
            if ($type->isClassStringType()->yes()) {
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
            if (count($matches) > 0) {
                return new ConstantArrayType(
                    [new ConstantIntegerType(0), new ConstantIntegerType(1)],
                    [
                        new StaticType($this->reflectionProvider->getClass($matches[1])),
                        new ConstantStringType($matches[2])
                    ]
                );
            }
        }
        return $type;
    }
}
