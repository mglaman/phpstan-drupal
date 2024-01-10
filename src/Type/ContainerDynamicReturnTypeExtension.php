<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
        $methodName = $methodReflection->getName();

        if ($methodName === 'has') {
            $args = $methodCall->getArgs();
            if (count($args) !== 1) {
                return $returnType;
            }

            $types = [];
            $argType = $scope->getType($args[0]->value);

            foreach ($argType->getConstantStrings() as $constantStringType) {
                $serviceId = $constantStringType->getValue();
                $service = $this->serviceMap->getService($serviceId);
                $types[] = new ConstantBooleanType($service !== null);
            }

            return TypeCombinator::union(...$types);
        } elseif ($methodName === 'get') {
            $args = $methodCall->getArgs();
            if (count($args) === 0) {
                return $returnType;
            }

            $types = [];

            if (isset($args[1])) {
                $invalidBehaviour = $scope->getType($args[1]->value);

                foreach ($invalidBehaviour->getConstantScalarValues() as $value) {
                    if ($value === ContainerInterface::NULL_ON_INVALID_REFERENCE) {
                        $types[] = new NullType();
                        break;
                    }
                }
            }

            $argType = $scope->getType($args[0]->value);

            foreach ($argType->getConstantStrings() as $constantStringType) {
                $serviceId = $constantStringType->getValue();
                $service = $this->serviceMap->getService($serviceId);
                $types[] = $service !== null ? $service->getType() : $returnType;
            }

            return TypeCombinator::union(...$types);
        }

        return $returnType;
    }
}
