<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Psr\Container\ContainerInterface;

class ContainerDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @var ServiceMap
     */
    private ServiceMap $serviceMap;

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
    ): Type {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        $args = $methodCall->getArgs();
        if (count($args) !== 1) {
            return $returnType;
        }

        $methodName = $methodReflection->getName();
        $types = [];
        $argType = $scope->getType($args[0]->value);

        foreach ($argType->getConstantStrings() as $constantStringType) {
            $serviceId = $constantStringType->getValue();
            $service = $this->serviceMap->getService($serviceId);
            if ($methodName === 'get') {
                $types[] = $service !== null ? $service->getType() : $returnType;
            } elseif ($methodName === 'has') {
                $types[] = new ConstantBooleanType($service !== null);
            }
        }
        return TypeCombinator::union(...$types);
    }
}
