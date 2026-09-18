<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal;
use mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

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
        return Drupal::class;
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
        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();
        if (!isset($methodCall->args[0])) {
            return $returnType;
        }

        $arg1 = $methodCall->args[0];
        // An element of $methodCall->args is one of three node types:
        // - Arg: a regular argument such as \Drupal::service('foo'). This is
        //   the only type that carries a value we can inspect.
        // - VariadicPlaceholder: the "..." in first-class callable syntax,
        //   \Drupal::service(...).
        // - ArgPlaceholder (nikic/php-parser 5.9+): the "?" in partial function
        //   application, \Drupal::service(?).
        // The two placeholders create a callable instead of making a call, so
        // PHPStan never asks this extension to resolve them.
        if (!$arg1 instanceof Arg) {
            throw new ShouldNotHappenException();
        }

        $arg1 = $arg1->value;

        if ($arg1 instanceof String_) {
            $serviceId = $arg1->value;
            return $this->getServiceType($serviceId) ?? $returnType;
        }

        if ($arg1 instanceof ClassConstFetch && $arg1->class instanceof FullyQualified) {
            $serviceId = (string) $arg1->class;
            return $this->getServiceType($serviceId) ?? $returnType;
        }

        return $returnType;
    }

    protected function getServiceType(string $serviceId): ?Type
    {
        $service = $this->serviceMap->getService($serviceId);
        if ($service instanceof DrupalServiceDefinition) {
            return $service->getType();
        }

        return null;
    }
}
