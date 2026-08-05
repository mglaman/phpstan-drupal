<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use mglaman\PHPStanDrupal\Drupal\EntityDataRepository;
use mglaman\PHPStanDrupal\Type\EntityStorage\EntityStorageType;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
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
        if ($methodCall->isFirstClassCallable()) {
            return $returnType;
        }
        $args = $methodCall->getArgs();
        if (count($args) === 0) {
            // Calling getStorage() without arguments is invalid, but PHPStan
            // reports that itself; do not crash the analysis.
            return $returnType;
        }

        $arg1 = $args[0]->value;

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

        $classNames = $returnType->getObjectClassNames();
        if (count($classNames) === 1) {
            return new EntityStorageType($entityTypeId, $classNames[0]);
        }
        return $returnType;
    }
}
