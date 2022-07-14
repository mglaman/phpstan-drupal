<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use mglaman\PHPStanDrupal\Drupal\EntityDataRepository;
use mglaman\PHPStanDrupal\Type\EntityQuery\ConfigEntityQueryType;
use mglaman\PHPStanDrupal\Type\EntityQuery\ContentEntityQueryType;
use mglaman\PHPStanDrupal\Type\EntityQuery\EntityQueryType;
use mglaman\PHPStanDrupal\Type\EntityStorage\ConfigEntityStorageType;
use mglaman\PHPStanDrupal\Type\EntityStorage\ContentEntityStorageType;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class DrupalStaticEntityQueryDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
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
        return \Drupal::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'entityQuery';
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        if (!$returnType instanceof ObjectType) {
            return $returnType;
        }
        $args = $methodCall->getArgs();
        if (count($args) !== 1) {
            return $returnType;
        }
        $type = $scope->getType($args[0]->value);
        if ($type instanceof ConstantStringType) {
            $entityTypeId = $type->getValue();
        } else {
            // @todo determine what these types are, and try to resolve entity name from.
            return $returnType;
        }
        $entityType = $this->entityDataRepository->get($entityTypeId);
        $entityStorageType = $entityType->getStorageType();
        if ($entityStorageType === null) {
            return $returnType;
        }

        if ($entityStorageType instanceof ContentEntityStorageType) {
            return new ContentEntityQueryType(
                $returnType->getClassName(),
                $returnType->getSubtractedType(),
                $returnType->getClassReflection()
            );
        }
        if ($entityStorageType instanceof ConfigEntityStorageType) {
            return new ConfigEntityQueryType(
                $returnType->getClassName(),
                $returnType->getSubtractedType(),
                $returnType->getClassReflection()
            );
        }

        return new EntityQueryType(
            $returnType->getClassName(),
            $returnType->getSubtractedType(),
            $returnType->getClassReflection()
        );
    }
}
