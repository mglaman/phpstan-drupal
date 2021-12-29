<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Analyser\Scope;
use mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use Psr\Container\ContainerInterface;

class ContainerDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
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
        return ContainerInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['get', 'has'], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): \PHPStan\Type\Type {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        if (!isset($methodCall->args[0])) {
            return $returnType;
        }

        $arg1 = $methodCall->args[0];
        if ($arg1 instanceof VariadicPlaceholder) {
            throw new ShouldNotHappenException();
        }
        $arg1 = $arg1->value;
        if (!$arg1 instanceof String_) {
            // @todo determine what these types are.
            return $returnType;
        }

        $serviceId = $arg1->value;

        if ($methodReflection->getName() === 'get') {
            $service = $this->serviceMap->getService($serviceId);
            if ($service instanceof DrupalServiceDefinition) {
                return $service->getType();
            }
            return $returnType;
        }

        if ($methodReflection->getName() === 'has') {
            return new ConstantBooleanType($this->serviceMap->getService($serviceId) instanceof DrupalServiceDefinition);
        }

        throw new ShouldNotHappenException();
    }
}
