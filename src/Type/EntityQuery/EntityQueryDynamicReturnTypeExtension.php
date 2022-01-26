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
        return true;
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
            if ($varType instanceof ConfigEntityQueryType) {
                return new ArrayType(new StringType(), new StringType());
            }
            if ($varType instanceof ContentEntityQueryType) {
                return new ArrayType(new IntegerType(), new StringType());
            }
            return $defaultReturnType;
        }

        // Fallback for all other methods on \Drupal\Core\Entity\Query\QueryInterface
        // to ensure the default return type of `$this` is the correct type from the
        // parent caller. The default return type is cached with the entity storage property.
        // @todo This means we're doing something wrong. But this makes it work.
        // The default return type of EntityQueryType can be cached with an invalid entity storage type.
        if ($defaultReturnType->equals($varType)) {
            return $varType;
        }

        return $defaultReturnType;
    }
}
