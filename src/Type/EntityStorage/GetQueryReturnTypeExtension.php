<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityStorage;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use mglaman\PHPStanDrupal\Type\EntityQuery\ConfigEntityQueryType;
use mglaman\PHPStanDrupal\Type\EntityQuery\ContentEntityQueryType;
use mglaman\PHPStanDrupal\Type\EntityQuery\EntityQueryType;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;

final class GetQueryReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    public function getClass(): string
    {
        return EntityStorageInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
            'getQuery',
            'getAggregateQuery',
        ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): \PHPStan\Type\Type {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        if (!$returnType instanceof ObjectType) {
            return $returnType;
        }

        $callerType = $scope->getType($methodCall->var);
        if (!$callerType instanceof ObjectType) {
            return $returnType;
        }

        if ((new ObjectType(ContentEntityStorageInterface::class))->isSuperTypeOf($callerType)->yes()) {
            return new ContentEntityQueryType(
                $returnType->getClassName(),
                $returnType->getSubtractedType(),
                $returnType->getClassReflection()
            );
        }
        if ((new ObjectType(ConfigEntityStorageInterface::class))->isSuperTypeOf($callerType)->yes()) {
            return new ConfigEntityQueryType(
                $returnType->getClassName(),
                $returnType->getSubtractedType(),
                $returnType->getClassReflection()
            );
        }
        return new EntityQueryType(
            $returnType->getClassName(),
            $returnType->getSubtractedType(),
            $returnType->getClassReflection()
        );
    }
}
