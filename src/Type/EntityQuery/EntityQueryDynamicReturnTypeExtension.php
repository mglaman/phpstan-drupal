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

        if (!$varType instanceof ObjectType) {
            return $defaultReturnType;
        }

        if ($methodName === 'count') {
            if ($varType instanceof EntityQueryType) {
                return $varType->asCount();
            }
            // By now we are sure we can't determine anything about what query
            // the count method is on, so we ignore it.
            return $defaultReturnType;
        }

        if ($methodName === 'execute') {
            if (!$varType instanceof EntityQueryType) {
                return $defaultReturnType;
            }
            if ($varType->isCount()) {
                return $varType->hasAccessCheck()
                    ? new IntegerType()
                    : new EntityQueryExecuteWithoutAccessCheckCountType();
            }
            if ($varType instanceof ConfigEntityQueryType) {
                return $varType->hasAccessCheck()
                    ? new ArrayType(new StringType(), new StringType())
                    : new EntityQueryExecuteWithoutAccessCheckType(new StringType(), new StringType());
            }
            if ($varType instanceof ContentEntityQueryType) {
                return $varType->hasAccessCheck()
                    ? new ArrayType(new IntegerType(), new StringType())
                    : new EntityQueryExecuteWithoutAccessCheckType(new IntegerType(), new StringType());
            }
            return $varType->hasAccessCheck()
                ? new ArrayType(new IntegerType(), new StringType())
                : new EntityQueryExecuteWithoutAccessCheckType(new IntegerType(), new StringType());
        }

        return $defaultReturnType;
    }
}
