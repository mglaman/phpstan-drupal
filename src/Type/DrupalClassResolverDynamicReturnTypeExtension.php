<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use PhpParser\Node\Expr\MethodCall;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

class DrupalClassResolverDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @var ServiceMap
     */
    private $serviceMap;

    public function __construct(ServiceMap $serviceMap)
    {
        $this->serviceMap = $serviceMap;
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
        if (0 === \count($methodCall->getArgs())) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        return DrupalClassResolverReturnType::getType($methodReflection, $methodCall, $scope, $this->serviceMap);
    }
}
