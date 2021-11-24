<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityStorage;

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
use PHPStan\Type\TypeCombinator;

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
        return \in_array(
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
    ): \PHPStan\Type\Type {
        $callerType = $scope->getType($methodCall->var);

        if (!$callerType instanceof EntityStorageType) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $type = $this->entityDataRepository->get($callerType->getEntityTypeId())->getClassType();
        if ($type === null) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }
        if (\in_array($methodReflection->getName(), ['load', 'loadUnchanged'], true)) {
            return TypeCombinator::addNull($type);
        }

        if (\in_array($methodReflection->getName(), ['loadMultiple', 'loadByProperties'], true)) {
            if ($callerType instanceof ConfigEntityStorageType) {
                return new ArrayType(new StringType(), $type);
            }

            return new ArrayType(new IntegerType(), $type);
        }

        return $type;
    }
}
