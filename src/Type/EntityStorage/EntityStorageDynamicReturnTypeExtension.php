<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityStorage;

use Drupal\Core\Entity\EntityStorageInterface;
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
     * @var string[]
     */
    private $entityStorageMapping;

    /**
     * EntityStorageDynamicReturnTypeExtension constructor.
     *
     * @param string[] $entityStorageMapping
     */
    public function __construct(
        array $entityStorageMapping = []
    ) {
        $this->entityStorageMapping = $entityStorageMapping;
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

        $entityClassName = $this->entityStorageMapping[$callerType->getEntityTypeId()] ?? null;
        if (null === $entityClassName) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $type = new ObjectType($entityClassName);
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
