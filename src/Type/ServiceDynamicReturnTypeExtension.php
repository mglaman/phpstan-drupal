<?php declare(strict_types=1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Drupal\DrupalServiceDefinition;
use PHPStan\Drupal\ServiceMap;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;

class ServiceDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
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
        return 'Symfony\Component\DependencyInjection\ContainerInterface';
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['get', 'has'], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        if (!isset($methodCall->args[0])) {
            return $returnType;
        }

        $arg1 = $methodCall->args[0]->value;
        if (!$arg1 instanceof String_) {
            // @todo determine what these types are.
            return $returnType;
        }

        $serviceId = $arg1->value;

        if ($methodReflection->getName() === 'get') {
            $service = $this->serviceMap->getService($serviceId);
            if ($service instanceof DrupalServiceDefinition) {
                return new ObjectType($service->getClass() ?? $serviceId);
            }
            return $returnType;
        }

        if ($methodReflection->getName() === 'has') {
            return new ConstantBooleanType($this->serviceMap->getService($serviceId) instanceof DrupalServiceDefinition);
        }

        throw new ShouldNotHappenException();
    }
}
