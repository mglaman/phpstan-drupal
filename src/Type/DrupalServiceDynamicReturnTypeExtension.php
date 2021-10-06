<?php declare(strict_types=1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Analyser\Scope;
use PHPStan\Drupal\DrupalServiceDefinition;
use PHPStan\Drupal\ServiceMap;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;

class DrupalServiceDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
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
        return \Drupal::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'service';
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
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
        $service = $this->serviceMap->getService($serviceId);
        if ($service instanceof DrupalServiceDefinition) {
            // Work around Drupal misusing the SplString class for string
            // pseudo-services such as 'app.root'.
            // @see https://www.drupal.org/project/drupal/issues/3074585
            if ($service->getClass() === 'SplString') {
                return new StringType();
            }
            return new ObjectType($service->getClass() ?? $serviceId);
        }
        return $returnType;
    }
}
