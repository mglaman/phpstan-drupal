<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityStorage;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use mglaman\PHPStanDrupal\Drupal\EntityDataRepository;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function in_array;

class EntityStorageDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    /**
     * @var EntityDataRepository
     */
    private $entityDataRepository;

    public function __construct(EntityDataRepository $entityDataRepository)
    {
        $this->entityDataRepository = $entityDataRepository;
    }

    public function getClass(): string
    {
        return EntityStorageInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array(
            $methodReflection->getName(),
            [
                'create',
                'load',
                'loadMultiple',
                'loadByProperties',
                'loadUnchanged',
            ],
            true
        );
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $callerType = $scope->getType($methodCall->var);
        if (!$callerType instanceof ObjectType) {
            return ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $methodCall->getArgs(),
                $methodReflection->getVariants()
            )->getReturnType();
        }

        if (!$callerType instanceof EntityStorageType) {
            $resolvedEntityType = $this->entityDataRepository->resolveFromStorage($callerType);
            if ($resolvedEntityType === null) {
                return ParametersAcceptorSelector::selectFromArgs(
                    $scope,
                    $methodCall->getArgs(),
                    $methodReflection->getVariants()
                )->getReturnType();
            }
            $type = $resolvedEntityType->getClassType();
        } else {
            $type = $this->entityDataRepository->get($callerType->getEntityTypeId())->getClassType();
        }

        if ($type === null) {
            return ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $methodCall->getArgs(),
                $methodReflection->getVariants()
            )->getReturnType();
        }
        if (in_array($methodReflection->getName(), ['load', 'loadUnchanged'], true)) {
            return TypeCombinator::addNull($type);
        }

        if (in_array($methodReflection->getName(), ['loadMultiple', 'loadByProperties'], true)) {
            if ((new ObjectType(ConfigEntityStorageInterface::class))->isSuperTypeOf($callerType)->yes()) {
                return new ArrayType(new StringType(), $type);
            }

            return new ArrayType(new IntegerType(), $type);
        }

        if ($methodReflection->getName() === 'create') {
            return $type;
        }

        return ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();
    }
}
