<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResultInterface;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class AccessibleReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    public function getClass(): string
    {
        return AccessibleInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'access';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        if (\count($methodCall->getArgs()) < 3) {
            return new BooleanType();
        }
        $returnAsObjectArg = $scope->getType($methodCall->getArgs()[2]->value);
        if (!$returnAsObjectArg instanceof ConstantBooleanType) {
            return $returnType;
        }

        return $returnAsObjectArg->getValue() ? new ObjectType(AccessResultInterface::class) : new BooleanType();
    }
}
