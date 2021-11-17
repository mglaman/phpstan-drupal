<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityQuery;

use Drupal\Core\Entity\Query\QueryInterface;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class EntityQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    public function getClass(): string
    {
        return QueryInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
            'count',
            'execute',
        ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $defaultReturnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        $varType = $scope->getType($methodCall->var);
        $methodName = $methodReflection->getName();
        if ($methodName === 'count') {
            if ($varType instanceof ObjectType) {
                return new EntityQueryCountType(
                    $varType->getClassName(),
                    $varType->getSubtractedType(),
                    $varType->getClassReflection()
                );
            }
            return $defaultReturnType;
        }

        if ($methodName === 'execute') {
            if ($varType instanceof EntityQueryCountType) {
                return new IntegerType();
            }
            if ($varType instanceof ObjectType) {
                // @todo if this is a config storage, it'd string keys.
                // revisit after https://github.com/mglaman/phpstan-drupal/pull/239
                // then we can check what kind of storage we have.
                return new ArrayType(new IntegerType(), new StringType());
            }
            return $defaultReturnType;
        }
        return $defaultReturnType;
    }
}
