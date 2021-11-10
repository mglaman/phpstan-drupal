<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PhpParser\Node\Expr\CallLike;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class DrupalClassResolverReturnType
{

    public static function getType(
        MethodReflection $methodReflection,
        CallLike $methodCall,
        Scope $scope,
        ServiceMap $serviceMap
    ): Type {
        $arg1 = $scope->getType($methodCall->getArgs()[0]->value);
        if (!$arg1 instanceof ConstantStringType) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $serviceDefinition = $serviceMap->getService($arg1->getValue());
        if ($serviceDefinition instanceof DrupalServiceDefinition) {
            return $serviceDefinition->getType();
        }

        return new ObjectType($arg1->getValue());
    }
}
