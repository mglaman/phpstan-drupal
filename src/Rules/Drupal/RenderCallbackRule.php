<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Render\Element\RenderCallbackInterface;
use Drupal\Core\Render\PlaceholderGenerator;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\Security\TrustedCallbackInterface;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use mglaman\PHPStanDrupal\Drupal\ServiceResultCacheValueExtension;
use mglaman\PHPStanDrupal\Drupal\TrustedCallbacksReader;
use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Name;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function array_merge;
use function class_exists;
use function count;
use function explode;
use function in_array;
use function preg_match;
use function sprintf;
use function str_contains;
use function substr_count;

/**
 * @implements Rule<Node\Expr\ArrayItem>
 */
final class RenderCallbackRule implements Rule
{

    private ReflectionProvider $reflectionProvider;

    private ServiceMap $serviceMap;

    private TrustedCallbacksReader $trustedCallbacksReader;

    private array $supportedKeys = [
        '#pre_render',
        '#post_render',
        '#access_callback',
        '#lazy_builder',
        '#date_time_callbacks',
        '#date_date_callbacks',
    ];

    public function __construct(
        ReflectionProvider $reflectionProvider,
        ServiceMap $serviceMap,
        TrustedCallbacksReader $trustedCallbacksReader
    ) {
        $this->reflectionProvider = $reflectionProvider;
        $this->serviceMap = $serviceMap;
        $this->trustedCallbacksReader = $trustedCallbacksReader;
    }

    public function getNodeType(): string
    {
        return ArrayItem::class;
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $key = $node->key;
        if (!$key instanceof Node\Scalar\String_) {
            return [];
        }

        // @see https://www.drupal.org/node/2966725
        $keySearch = array_search($key->value, $this->supportedKeys, true);
        if ($keySearch === false) {
            return [];
        }
        $keyChecked = $this->supportedKeys[$keySearch];
        $value = $node->value;

        if ($keyChecked === '#access_callback') {
            return $this->doProcessNode($node->value, $scope, $keyChecked, 0);
        }

        if ($keyChecked === '#lazy_builder') {
            if ($scope->isInClass()) {
                $classReflection = $scope->getClassReflection();
                $classType = new ObjectType($classReflection->getName());
                // These classes use #lazy_builder in array_intersect_key. With
                // PHPStan 1.6, nodes do not track their parent/next/prev which
                // saves a lot of memory. But makes it harder to detect if we're
                // in a call to array_intersect_key. This is an easier workaround.
                $allowedTypes = new UnionType([
                    new ObjectType(PlaceholderGenerator::class),
                    new ObjectType(Renderer::class),
                    new ObjectType('Drupal\Tests\Core\Render\RendererPlaceholdersTest'),
                ]);
                if ($allowedTypes->isSuperTypeOf($classType)->yes()) {
                    return [];
                }
            }

            if (!$value instanceof Node\Expr\Array_) {
                return [
                    RuleErrorBuilder::message(sprintf('The "%s" expects a callable array with arguments.', $keyChecked))
                        ->line($node->getStartLine())
                        ->identifier('renderCallback.expectsCallableArrayWithArguments')
                        ->build()
                ];
            }
            if (count($value->items) === 0) {
                return [];
            }
            // @todo take $value->items[1] and validate parameters against the callback.
            return $this->doProcessNode($value->items[0]->value, $scope, $keyChecked, 0);
        }

        if (!$value instanceof Node\Expr\Array_) {
            return [
                RuleErrorBuilder::message(sprintf('The "%s" render array value expects an array of callbacks.', $keyChecked))
                    ->line($node->getStartLine())
                    ->identifier('renderCallback.expectsArrayOfCallbacks')
                    ->build()
            ];
        }
        if (count($value->items) === 0) {
            return [];
        }
        $errors = [];
        foreach ($value->items as $pos => $item) {
            $errors[] = $this->doProcessNode($item->value, $scope, $keyChecked, $pos);
        }
        return array_merge(...$errors);
    }

    /**
     * @param Scope&DependencyTracker $scope
     * @return list<\PHPStan\Rules\IdentifierRuleError> errors
     */
    private function doProcessNode(Node\Expr $node, Scope $scope, string $keyChecked, int $pos): array
    {
        $checkIsCallable = true;

        $trustedCallbackType = new UnionType([
            new ObjectType(TrustedCallbackInterface::class),
            new ObjectType(RenderCallbackInterface::class),
        ]);

        $errors = [];
        $errorLine = $node->getStartLine();
        $type = $this->getType($node, $scope);

        foreach ($type->getConstantStrings() as $constantStringType) {
            if (!$constantStringType->isCallable()->yes()) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf("%s callback %s at key '%s' is not callable.", $keyChecked, $constantStringType->describe(VerbosityLevel::value()), $pos)
                )->line($errorLine)
                ->identifier('renderCallback.nonCallableCallback')
                ->build();
            } elseif ($this->reflectionProvider->hasFunction(new Name($constantStringType->getValue()), null)) {
                // We can determine if the callback is callable through the type system. However, we cannot determine
                // if it is just a function or a static class call (MyClass::staticFunc).
                $errors[] = RuleErrorBuilder::message(
                    sprintf("%s callback %s at key '%s' is not trusted.", $keyChecked, $constantStringType->describe(VerbosityLevel::value()), $pos)
                )->line($errorLine)
                ->tip('Change record: https://www.drupal.org/node/2966725.')
                ->identifier('renderCallback.nonTrustedCallback')
                ->build();
            } else {
                // @see \PHPStan\Type\Constant\ConstantStringType::isCallable
                preg_match('#^([a-zA-Z_\\x7f-\\xff\\\\][a-zA-Z0-9_\\x7f-\\xff\\\\]*)::([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)\\z#', $constantStringType->getValue(), $matches);
                if (count($matches) === 0) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf("%s callback %s at key '%s' is not callable.", $keyChecked, $constantStringType->describe(VerbosityLevel::value()), $pos)
                    )->line($errorLine)
                    ->identifier('renderCallback.nonCallableCallback')
                    ->build();
                } elseif (!$trustedCallbackType->isSuperTypeOf(new ObjectType($matches[1]))->yes()) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf("%s callback class %s at key '%s' does not implement Drupal\Core\Security\TrustedCallbackInterface.", $keyChecked, $constantStringType->describe(VerbosityLevel::value()), $pos)
                    )->line($errorLine)->tip('Change record: https://www.drupal.org/node/2966725.')
                    ->identifier('renderCallback.callbackNotImplementsTrustedCallbackInterface')
                    ->build();
                }
            }
        }

        foreach ($type->getConstantArrays() as $constantArrayType) {
            if (!$constantArrayType->isCallable()->yes()) {
                // If the right-hand side of the array is a variable, we cannot
                // determine if it is callable. Bail now.
                $itemType = $constantArrayType->getItemType();
                if ($itemType instanceof UnionType) {
                    $unionConstantStrings = array_merge(...array_map(static function (Type $type) {
                        return $type->getConstantStrings();
                    }, $itemType->getTypes()));
                    if (count($unionConstantStrings) === 0) {
                        // Right-hand side of UnionType is not a constant string. We cannot determine if the dynamic
                        // value is callable or not.
                        $checkIsCallable = false;
                        break;
                    }
                }
                $errors[] = RuleErrorBuilder::message(
                    sprintf("%s callback %s at key '%s' is not callable.", $keyChecked, $constantArrayType->describe(VerbosityLevel::value()), $pos)
                )->line($errorLine)
                ->identifier('renderCallback.nonCallableCallback')
                ->build();
                continue;
            }
            $typeAndMethodNames = $constantArrayType->findTypeAndMethodNames();
            if ($typeAndMethodNames === []) {
                continue;
            }

            foreach ($typeAndMethodNames as $typeAndMethodName) {
                $isTrustedCallbackAttribute = TrinaryLogic::createNo()->lazyOr(
                    $typeAndMethodName->getType()->getObjectClassReflections(),
                    function (ClassReflection $reflection) use ($typeAndMethodName) {
                        if (!class_exists(TrustedCallback::class)) {
                            return TrinaryLogic::createNo();
                        }
                        $hasAttribute = $reflection->getNativeReflection()
                            ->getMethod($typeAndMethodName->getMethod())
                            ->getAttributes(TrustedCallback::class);
                        return TrinaryLogic::createFromBoolean(count($hasAttribute) > 0);
                    }
                );

                $isTrustedCallbackInterfaceType = $trustedCallbackType->isSuperTypeOf($typeAndMethodName->getType())->yes();
                if (!$isTrustedCallbackInterfaceType && !$isTrustedCallbackAttribute->yes()) {
                    if (class_exists(TrustedCallback::class)) {
                        $errors[] =  RuleErrorBuilder::message(
                            sprintf(
                                "%s callback method '%s' at key '%s' does not implement attribute \Drupal\Core\Security\Attribute\TrustedCallback.",
                                $keyChecked,
                                $constantArrayType->describe(VerbosityLevel::value()),
                                $pos
                            )
                        )->line($errorLine)->tip('Change record: https://www.drupal.org/node/3349470')
                        ->identifier('renderCallback.callbackNotImplementsTrustedCallback')
                        ->build();
                    } else {
                        $errors[] =  RuleErrorBuilder::message(
                            sprintf(
                                "%s callback class '%s' at key '%s' does not implement Drupal\Core\Security\TrustedCallbackInterface.",
                                $keyChecked,
                                $typeAndMethodName->getType()->describe(VerbosityLevel::value()),
                                $pos
                            )
                        )->line($errorLine)->tip('Change record: https://www.drupal.org/node/2966725.')
                        ->identifier('renderCallback.callbackNotImplementsTrustedCallbackInterface')
                        ->build();
                    }
                } elseif (!$isTrustedCallbackAttribute->yes()) {
                    $trustedCallbacksClass = $this->findTrustedCallbacksClass(
                        $typeAndMethodName->getType(),
                        $this->namesExactCallbackClass($scope->getType($node), $constantArrayType)
                    );
                    if ($trustedCallbacksClass === null) {
                        continue;
                    }
                    $trustedCallbacks = $this->trustedCallbacksReader->read($trustedCallbacksClass, $scope);
                    if ($trustedCallbacks === null || in_array($typeAndMethodName->getMethod(), $trustedCallbacks, true)) {
                        continue;
                    }
                    $errors[] = RuleErrorBuilder::message(
                        sprintf(
                            "%s callback method '%s' at key '%s' is not returned by %s::trustedCallbacks().",
                            $keyChecked,
                            $typeAndMethodName->getMethod(),
                            $pos,
                            $trustedCallbacksClass->getName()
                        )
                    )->line($errorLine)->tip('Change record: https://www.drupal.org/node/2966725.')
                    ->identifier('renderCallback.methodNotInTrustedCallbacks')
                    ->build();
                }
            }
        }
        // @todo move to its own rule for 1.2.0, FormClosureSerializationRule.
        if (($type instanceof ClosureType) && $scope->isInClass()) {
            $classReflection = $scope->getClassReflection();
            $classType = new ObjectType($classReflection->getName());
            $formType = new ObjectType('\Drupal\Core\Form\FormInterface');
            if ($formType->isSuperTypeOf($classType)->yes()) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf("%s may not contain a closure at key '%s' as forms may be serialized and serialization of closures is not allowed.", $keyChecked, $pos)
                )->line($errorLine)
                ->identifier('renderCallback.containsClosure')
                ->build();
            }
        }

        if (count($errors) === 0 && ($checkIsCallable && !$type->isCallable()->yes())) {
            $errors[] = RuleErrorBuilder::message(
                sprintf("%s value '%s' at key '%s' is invalid.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
            )->line($errorLine)
            ->identifier('renderCallback.invalidKeyValue')
            ->build();
        }

        return $errors;
    }

    // @todo move to a helper, as Drupal uses `service:method` references a lot.
    /**
     * @param Scope&DependencyTracker $scope
     */
    private function getType(Node\Expr $node, Scope $scope):  Type
    {
        $type = $scope->getType($node);
        // Covers concatenation of static::class . '::methodName'.
        if ($node instanceof Node\Expr\BinaryOp\Concat) {
            $leftType = $scope->getType($node->left);
            $rightType = $scope->getType($node->right);
            $rightConstantStrings = $rightType->getConstantStrings();
            if (count($rightConstantStrings) > 0 && $leftType->isClassString()->yes() && $leftType->getClassStringObjectType() instanceof StaticType) {
                return new ConstantArrayType(
                    [new ConstantIntegerType(0), new ConstantIntegerType(1)],
                    [
                        $leftType->getClassStringObjectType(),
                        new ConstantStringType(ltrim($rightConstantStrings[0]->getValue(), ':'))
                    ]
                );
            }
        }

        $constantStrings = $type->getConstantStrings();
        if (count($constantStrings) > 0) {
            $constantString = $constantStrings[0];
            if ($constantString->isClassString()->yes()) {
                return $type;
            }
            // Covers \Drupal\Core\Controller\ControllerResolver::createController.
            if (substr_count($constantString->getValue(), ':') === 1) {
                [$class_or_service, $method] = explode(':', $constantString->getValue(), 2);

                $scope->trackValueDependency(ServiceResultCacheValueExtension::class, $class_or_service);
                $serviceDefinition = $this->serviceMap->getService($class_or_service);
                if ($serviceDefinition === null || $serviceDefinition->getClass() === null) {
                    return $type;
                }
                return new ConstantArrayType(
                    [new ConstantIntegerType(0), new ConstantIntegerType(1)],
                    [
                        new ObjectType($serviceDefinition->getClass()),
                        new ConstantStringType($method)
                    ]
                );
            }
            // @see \PHPStan\Type\Constant\ConstantStringType::isCallable
            preg_match('#^([a-zA-Z_\\x7f-\\xff\\\\][a-zA-Z0-9_\\x7f-\\xff\\\\]*)::([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)\\z#', $constantString->getValue(), $matches);
            if (count($matches) === 3) {
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

    /**
     * Whether Drupal calls trustedCallbacks() on the class the callback names.
     *
     * A callback like [$this, 'method'] runs on an object that may be a
     * subclass, and a subclass may override trustedCallbacks().
     */
    private function namesExactCallbackClass(Type $callbackType, ConstantArrayType $callbackArray): bool
    {
        $callbackStrings = $callbackType->getConstantStrings();
        if (count($callbackStrings) > 0) {
            foreach ($callbackStrings as $callbackString) {
                if (!str_contains($callbackString->getValue(), '::')) {
                    return false;
                }
            }
            return true;
        }
        $classType = $callbackArray->getValueTypes()[0] ?? null;
        return $classType !== null && count($classType->getConstantStrings()) > 0;
    }

    /**
     * Finds the class whose trustedCallbacks() decides if the callback runs.
     *
     * Returns NULL when that class is not certain, or when Drupal trusts the
     * callback without asking trustedCallbacks().
     */
    private function findTrustedCallbacksClass(Type $callbackClassType, bool $namesExactCallbackClass): ?ClassReflection
    {
        $classReflections = $callbackClassType->getObjectClassReflections();
        if (count($classReflections) !== 1) {
            return null;
        }
        $classReflection = $classReflections[0];
        if (!$namesExactCallbackClass && !$classReflection->isFinal()) {
            return null;
        }
        if (!$classReflection->implementsInterface(TrustedCallbackInterface::class)
            || $classReflection->implementsInterface(RenderCallbackInterface::class)
        ) {
            return null;
        }
        return $classReflection;
    }
}
