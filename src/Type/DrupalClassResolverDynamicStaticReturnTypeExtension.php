<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function count;

class DrupalClassResolverDynamicStaticReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function __construct(
        private ServiceMap $serviceMap,
        private bool $classResolverReturnType = true,
    ) {
    }

    public function getClass(): string
    {
        return Drupal::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'classResolver';
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        if (0 === count($methodCall->getArgs())) {
            return new ObjectType(ClassResolverInterface::class);
        }

        if (!$this->classResolverReturnType) {
            return ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $methodCall->getArgs(),
                $methodReflection->getVariants()
            )->getReturnType();
        }

        return DrupalClassResolverReturnType::getType($methodReflection, $methodCall, $scope, $this->serviceMap);
    }
}
