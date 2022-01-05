<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\Php\PhpFunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeAndMethod;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

final class RenderCallbackRule implements Rule
{

    protected ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
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
        $keysToCheck = ['#pre_render', '#post_render', '#lazy_builder', '#access_callback'];
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

        $trustedCallbackType = new ObjectType('Drupal\Core\Security\TrustedCallbackInterface');
        $errors = [];
        foreach ($value->items as $pos => $item) {
            if (!$item instanceof Node\Expr\ArrayItem) {
                continue;
            }
            $errorLine = $item->value->getLine();
            $type = $scope->getType($item->value);

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
                        sprintf("%s callback %s at key '%s' is not trusted. See https://www.drupal.org/node/2966725.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                    )->line($errorLine)->build();
                    continue;
                }
                // @see \PHPStan\Type\Constant\ConstantStringType::isCallable
                preg_match('#^([a-zA-Z_\\x7f-\\xff\\\\][a-zA-Z0-9_\\x7f-\\xff\\\\]*)::([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)\\z#', $type->getValue(), $matches);
                if ($matches === null) {
                    throw new \PHPStan\ShouldNotHappenException('Unable to get class name from ConstantStringType value: ' . $type->describe(VerbosityLevel::value()));
                }
                if (!$trustedCallbackType->isSuperTypeOf(new ObjectType($matches[1]))->yes()) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf("%s callback class '%s' at key '%s' does not implement Drupal\Core\Security\TrustedCallbackInterface.", $keyChecked, (new ObjectType($matches[1]))->describe(VerbosityLevel::value()), $pos)
                    )->line($errorLine)->build();
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
                    )->line($errorLine)->build();
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
            }
            else {
                $errors[] = RuleErrorBuilder::message(
                    sprintf("%s value '%s' at key '%s' is invalid.", $keyChecked, $type->describe(VerbosityLevel::value()), $pos)
                )->line($errorLine)->build();
            }
        }

        return $errors;
    }

}
