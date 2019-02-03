<?php declare(strict_types=1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;

class EntityTypeManagerGetStorageDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @var array
     */
    private $entityTypeStorageMapping;

    public function __construct(array $entityTypeStorageMapping = [])
    {
        $this->entityTypeStorageMapping = $entityTypeStorageMapping;
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
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        if (!isset($methodCall->args[0])) {
            // Parameter is required.
            throw new ShouldNotHappenException();
        }

        $arg1 = $methodCall->args[0]->value;

        // @todo handle where the first param is EntityTypeInterface::id()
        if ($arg1 instanceof MethodCall) {
            // There may not be much that can be done, since it's a generic EntityTypeInterface.
            return $returnType;
        }
        // @todo handle concat ie: entity_{$display_context}_display for entity_form_display or entity_view_display
        if ($arg1 instanceof Concat) {
            return $returnType;
        }
        if (!$arg1 instanceof String_) {
            // @todo determine what these types are, and try to resolve entity name from.
            return $returnType;
        }

        $entityTypeId = $arg1->value;

        if (isset($this->entityTypeStorageMapping[$entityTypeId])) {
            return new ObjectType($this->entityTypeStorageMapping[$entityTypeId]);
        }
        return $returnType;
    }
}
