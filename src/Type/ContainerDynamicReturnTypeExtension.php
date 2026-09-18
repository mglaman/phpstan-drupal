<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function count;
use function in_array;

class ContainerDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    public function __construct(
        private ServiceMap $serviceMap,
        private bool $containerHasAlwaysTrue,
    ) {
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
        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();
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
                if (!$this->containerHasAlwaysTrue && $service !== null) {
                    $types[] = new BooleanType();
                } else {
                    $types[] = new ConstantBooleanType($service !== null);
                }
            }

            // A dynamic service ID has no constant strings; unioning zero
            // types would produce `never`.
            if ($types === []) {
                return $returnType;
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

            $constantStrings = $argType->getConstantStrings();
            foreach ($constantStrings as $constantStringType) {
                $serviceId = $constantStringType->getValue();
                $service = $this->serviceMap->getService($serviceId);
                $types[] = $service !== null ? $service->getType() : $returnType;
            }

            // A dynamic service ID has no constant strings; fall back to the
            // declared return type so the union cannot collapse to `never`,
            // while keeping any null added for NULL_ON_INVALID_REFERENCE.
            if ($constantStrings === []) {
                $types[] = $returnType;
            }

            return TypeCombinator::union(...$types);
        }

        return $returnType;
    }
}
