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
    ): ?Type {
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

        $entityObjectTypes = [];
        $entityIdArg = $scope->getType($methodArgs[0]->value);
        foreach ($entityIdArg->getConstantStrings() as $constantStringType) {
            $entityObjectTypes[] = $this->entityDataRepository->get($constantStringType->getValue())->getClassType() ?? $returnType;
        }
        $entityTypes = TypeCombinator::union(...$entityObjectTypes);

        if ($returnType->isArray()->no()) {
            if ($returnType->isNull()->maybe()) {
                $entityTypes = TypeCombinator::addNull($entityTypes);
            }
            return $entityTypes;
        }

        if ((new ObjectType(ConfigEntityInterface::class))->isSuperTypeOf($entityTypes)->yes()) {
            $keyType = new StringType();
        } else {
            $keyType = new IntegerType();
        }

        return new ArrayType($keyType, $entityTypes);
    }
}
