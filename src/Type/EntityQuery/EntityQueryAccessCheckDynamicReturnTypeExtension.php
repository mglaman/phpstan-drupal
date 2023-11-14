<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityQuery;

use Drupal\Core\Entity\Query\QueryInterface;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

class EntityQueryAccessCheckDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return QueryInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'accessCheck' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $varType = $scope->getType($methodCall->var);

        if (!$varType instanceof EntityQueryType) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        return $varType->withAccessCheck();
    }
}
