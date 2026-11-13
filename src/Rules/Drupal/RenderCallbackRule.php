<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Render\Element\RenderCallbackInterface;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\Security\TrustedCallbackInterface;
use mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback\RenderCallbackResolver;
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
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function array_merge;
use function class_exists;
use function count;
use function preg_match;
use function sprintf;

/**
 * @implements Rule<Node\Expr\ArrayItem>
 */
final class RenderCallbackRule implements Rule
{

    private ReflectionProvider $reflectionProvider;

    private RenderCallbackResolver $renderCallbackResolver;

    public function __construct(ReflectionProvider $reflectionProvider, RenderCallbackResolver $renderCallbackResolver)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->renderCallbackResolver = $renderCallbackResolver;
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
        $keyChecked = $this->renderCallbackResolver->findRenderKey($node, $scope);
        if ($keyChecked === null) {
            return [];
        }

        $callbacks = $this->renderCallbackResolver->findCallbackExpressions($keyChecked, $node->value);
        if ($callbacks === null && $keyChecked === '#lazy_builder') {
            return [
                RuleErrorBuilder::message(sprintf('The "%s" expects a callable array with arguments.', $keyChecked))
                    ->line($node->getStartLine())
                    ->identifier('renderCallback.expectsCallableArrayWithArguments')
                    ->build()
            ];
        }
        if ($callbacks === null) {
            return [
                RuleErrorBuilder::message(sprintf('The "%s" render array value expects an array of callbacks.', $keyChecked))
                    ->line($node->getStartLine())
                    ->identifier('renderCallback.expectsArrayOfCallbacks')
                    ->build()
            ];
        }
        $errors = [];
        foreach ($callbacks as $pos => $callback) {
            $errors[] = $this->doProcessNode($callback, $scope, $keyChecked, $pos);
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
        $type = $this->renderCallbackResolver->resolveType($node, $scope);

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
}
