<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use mglaman\PHPStanDrupal\Drupal\EntityDataRepository;
use mglaman\PHPStanDrupal\Type\EntityQuery\ConfigEntityQueryType;
use mglaman\PHPStanDrupal\Type\EntityQuery\ContentEntityQueryType;
use mglaman\PHPStanDrupal\Type\EntityQuery\EntityQueryType;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
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
        return Drupal::class;
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
        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();
        if (!$returnType instanceof ObjectType) {
            return $returnType;
        }
        $args = $methodCall->getArgs();
        if (count($args) !== 1) {
            return $returnType;
        }
        $type = $scope->getType($args[0]->value);

        if (count($type->getConstantStrings()) === 0) {
            // We're unsure what specific EntityQueryType it is, so let's stick
            // with the general class itself to ensure it gets access checked.
            return new EntityQueryType(
                $returnType->getClassName(),
                $returnType->getSubtractedType(),
                $returnType->getClassReflection()
            );
        }
        $entityTypeId = $type->getConstantStrings()[0]->getValue();
        $entityType = $this->entityDataRepository->get($entityTypeId);
        $entityStorageType = $entityType->getStorageType();
        if ($entityStorageType === null) {
            return $returnType;
        }

        if ((new ObjectType(ContentEntityStorageInterface::class))->isSuperTypeOf($entityStorageType)->yes()) {
            return new ContentEntityQueryType(
                $returnType->getClassName(),
                $returnType->getSubtractedType(),
                $returnType->getClassReflection()
            );
        }
        if ((new ObjectType(ConfigEntityStorageInterface::class))->isSuperTypeOf($entityStorageType)->yes()) {
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
