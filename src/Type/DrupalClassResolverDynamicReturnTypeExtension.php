<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use function count;

class DrupalClassResolverDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        private ServiceMap $serviceMap,
        private bool $classResolverReturnType = true,
    ) {
    }

    public function getClass(): string
    {
        return ClassResolverInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getInstanceFromDefinition';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        if (!$this->classResolverReturnType || 0 === count($methodCall->getArgs())) {
            return ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $methodCall->getArgs(),
                $methodReflection->getVariants()
            )->getReturnType();
        }

        return DrupalClassResolverReturnType::getType($methodReflection, $methodCall, $scope, $this->serviceMap);
    }
}
