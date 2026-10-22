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
use PHPStan\Type\Type;
use function count;
use function in_array;

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
    ): Type {
        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();
        if (!$returnType->isObject()->yes()) {
            return $returnType;
        }

        $objectClassNames = $returnType->getObjectClassNames();
        if (count($objectClassNames) !== 1) {
            return $returnType;
        }
        $className = $objectClassNames[0];

        $callerType = $scope->getType($methodCall->var);
        if (!$callerType->isObject()->yes()) {
            return $returnType;
        }

        $isAggregate = $methodReflection->getName() === 'getAggregateQuery';

        if ((new ObjectType(ContentEntityStorageInterface::class))->isSuperTypeOf($callerType)->yes()) {
            $queryType = new ContentEntityQueryType($className);
            return $isAggregate ? $queryType->asAggregate() : $queryType;
        }
        if ((new ObjectType(ConfigEntityStorageInterface::class))->isSuperTypeOf($callerType)->yes()) {
            // Core throws QueryException for an aggregate query over config
            // entities, so there is no aggregate query type to produce here.
            // @see \Drupal\Core\Config\Entity\Query\QueryFactory::getAggregate()
            return new ConfigEntityQueryType($className);
        }
        $queryType = new EntityQueryType($className);
        return $isAggregate ? $queryType->asAggregate() : $queryType;
    }
}
