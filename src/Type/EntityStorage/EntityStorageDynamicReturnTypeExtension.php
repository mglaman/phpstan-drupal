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
use PHPStan\Type\UnionType;
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
        $declaredReturnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();

        $callerType = $scope->getType($methodCall->var);
        if (!$callerType->isObject()->yes()) {
            return $declaredReturnType;
        }

        // A union of storages, such as getStorage($cond ? 'node' : 'user'),
        // resolves each member on its own so no branch is dropped.
        if ($callerType instanceof UnionType) {
            $types = [];
            foreach ($callerType->getTypes() as $storageType) {
                $types[] = $this->resolveForStorage($methodReflection, $storageType, $declaredReturnType);
            }
            return TypeCombinator::union(...$types);
        }

        return $this->resolveForStorage($methodReflection, $callerType, $declaredReturnType);
    }

    private function resolveForStorage(MethodReflection $methodReflection, Type $storageType, Type $declaredReturnType): Type
    {
        if ($storageType instanceof EntityStorageType) {
            $type = $this->entityDataRepository->get($storageType->getEntityTypeId())->getClassType();
        } else {
            $type = $this->entityDataRepository->resolveFromStorage($storageType)?->getClassType();
        }
        if ($type === null) {
            return $declaredReturnType;
        }

        $methodName = $methodReflection->getName();
        if (in_array($methodName, ['load', 'loadUnchanged'], true)) {
            return TypeCombinator::addNull($type);
        }

        if (in_array($methodName, ['loadMultiple', 'loadByProperties'], true)) {
            if ((new ObjectType(ConfigEntityStorageInterface::class))->isSuperTypeOf($storageType)->yes()) {
                return new ArrayType(new StringType(), $type);
            }

            return new ArrayType(new IntegerType(), $type);
        }

        if ($methodName === 'create') {
            return $type;
        }

        return $declaredReturnType;
    }
}
