<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use mglaman\PHPStanDrupal\Drupal\EntityDataRepository;
use mglaman\PHPStanDrupal\Type\EntityStorage\EntityQueryType;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
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
        if ($entityType->getStorageType() === null) {
            return $returnType;
        }
        // @todo I think we're doing things wrong if this return type gets cached.
        //   but  maybe we cannot do anything single we `selectSingle` from the default return type?
        //   We should probably just use a ConfigEntityQueryType and ContentEntityQueryType to avoid the problem.
        return new EntityQueryType(
            $returnType->getClassName(),
            $entityType->getStorageType(),
            $returnType->getSubtractedType(),
            $returnType->getClassReflection()
        );
    }
}
