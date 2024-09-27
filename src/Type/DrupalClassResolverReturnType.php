<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PhpParser\Node\Expr\CallLike;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function count;

final class DrupalClassResolverReturnType
{

    public static function getType(
        MethodReflection $methodReflection,
        CallLike $methodCall,
        Scope $scope,
        ServiceMap $serviceMap
    ): Type {
        $arg1 = $scope->getType($methodCall->getArgs()[0]->value);
        if (count($arg1->getConstantStrings()) === 0) {
            return ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $methodCall->getArgs(),
                $methodReflection->getVariants()
            )->getReturnType();
        }

        $serviceName = $arg1->getConstantStrings()[0];
        $serviceDefinition = $serviceMap->getService($serviceName->getValue());
        if ($serviceDefinition instanceof DrupalServiceDefinition) {
            return $serviceDefinition->getType();
        }

        return new ObjectType($serviceName->getValue());
    }
}
