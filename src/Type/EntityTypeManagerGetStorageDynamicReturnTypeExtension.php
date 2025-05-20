<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use mglaman\PHPStanDrupal\Drupal\EntityDataRepository;
use mglaman\PHPStanDrupal\Type\EntityStorage\EntityStorageType;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class EntityTypeManagerGetStorageDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    /**
     * @var EntityDataRepository
     */
    private $entityDataRepository;

    /**
     * EntityTypeManagerGetStorageDynamicReturnTypeExtension constructor.
     *
     * @param EntityDataRepository $entityDataRepository
     */
    public function __construct(EntityDataRepository $entityDataRepository)
    {
        $this->entityDataRepository = $entityDataRepository;
    }

    public function getClass(): string
    {
        return 'Drupal\Core\Entity\EntityTypeManagerInterface';
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getStorage';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $returnType = ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $methodCall->getArgs(),
            $methodReflection->getVariants()
        )->getReturnType();
        if (!isset($methodCall->args[0])) {
            // Parameter is required.
            throw new ShouldNotHappenException();
        }

        $arg1 = $methodCall->args[0];
        if ($arg1 instanceof VariadicPlaceholder) {
            throw new ShouldNotHappenException();
        }
        $arg1 = $arg1->value;

        // @todo handle where the first param is EntityTypeInterface::id()
        if ($arg1 instanceof MethodCall) {
            // There may not be much that can be done, since it's a generic EntityTypeInterface.
            return $returnType;
        }
        // @todo handle concat ie: entity_{$display_context}_display for entity_form_display or entity_view_display
        if ($arg1 instanceof Concat) {
            return $returnType;
        }

        $type = $scope->getType($arg1);
        if (count($type->getConstantStrings()) === 0) {
            return $returnType;
        }

        $entityTypeId = $type->getConstantStrings()[0]->getValue();
        $storageType = $this->entityDataRepository->get($entityTypeId)->getStorageType();
        if ($storageType !== null) {
            return $storageType;
        }

        if ($returnType instanceof ObjectType) {
            return new EntityStorageType($entityTypeId, $returnType->getClassName());
        }
        return $returnType;
    }
}
