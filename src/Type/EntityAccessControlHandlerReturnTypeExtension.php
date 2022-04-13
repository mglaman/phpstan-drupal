<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class EntityAccessControlHandlerReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    public function getClass(): string
    {
        return EntityAccessControlHandlerInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['access', 'createAccess', 'fieldAccess'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $returnType = new BooleanType();

        $args = $methodCall->getArgs();
        $arg = null;
        if ($methodReflection->getName() === 'access' && \count($args) === 4) {
            $arg = $args[3];
        }
        if ($methodReflection->getName() === 'createAccess' && \count($args) === 4) {
            $arg = $args[3];
        }
        if ($methodReflection->getName() === 'fieldAccess' && \count($args) === 5) {
            $arg = $args[4];
        }

        if ($arg === null) {
            return $returnType;
        }

        $returnAsObjectArg = $scope->getType($arg->value);
        if (!$returnAsObjectArg instanceof ConstantBooleanType) {
            return $returnType;
        }
        return $returnAsObjectArg->getValue() ? new ObjectType(AccessResultInterface::class) : new BooleanType();
    }
}
