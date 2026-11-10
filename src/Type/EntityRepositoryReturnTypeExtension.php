<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
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

final class EntityRepositoryReturnTypeExtension implements DynamicMethodReturnTypeExtension
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
        return EntityRepositoryInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array(
            $methodReflection->getName(),
            [
                'getTranslationFromContext',
                'loadEntityByUuid',
                'loadEntityByConfigTarget',
                'getActive',
                'getActiveMultiple',
                'getCanonical',
                'getCanonicalMultiple',
            ],
            true
        );
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $methodName = $methodReflection->getName();
        $methodArgs = $methodCall->getArgs();
        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();

        if (count($methodArgs) === 0) {
            return $returnType;
        }

        if ($methodName === 'getTranslationFromContext') {
            return $scope->getType($methodArgs[0]->value);
        }

        $entityIdArg = $scope->getType($methodArgs[0]->value);
        $returnsArray = $returnType->isArray()->yes();

        $resolvedTypes = [];
        foreach ($entityIdArg->getConstantStrings() as $constantStringType) {
            $classType = $this->entityDataRepository->get($constantStringType->getValue())->getClassType();
            if ($classType === null) {
                // The entity type ID is unknown, so nothing can be narrowed.
                return $returnType;
            }
            $resolvedTypes[] = $returnsArray ? new ArrayType($this->getKeyType($classType), $classType) : $classType;
        }
        if ($resolvedTypes === []) {
            return $returnType;
        }

        $resolvedType = TypeCombinator::union(...$resolvedTypes);
        if ($returnType->isNull()->maybe()) {
            $resolvedType = TypeCombinator::addNull($resolvedType);
        }
        return $resolvedType;
    }

    /**
     * Config entities are keyed by their string ID, content entities by their
     * integer ID.
     */
    private function getKeyType(Type $classType): Type
    {
        if ((new ObjectType(ConfigEntityInterface::class))->isSuperTypeOf($classType)->yes()) {
            return new StringType();
        }
        return new IntegerType();
    }
}
