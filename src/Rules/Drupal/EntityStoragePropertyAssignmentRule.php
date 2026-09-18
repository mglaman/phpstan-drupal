<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

/**
 * @implements Rule<Node\Expr\Assign>
 */
final class EntityStoragePropertyAssignmentRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Expr\Assign::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$scope->isInClass()) {
            return [];
        }

        if (!$node->var instanceof Node\Expr\PropertyFetch) {
            return [];
        }
        if (!$node->var->var instanceof Node\Expr\Variable || $node->var->var->name !== 'this') {
            return [];
        }

        $storageType = new ObjectType(EntityStorageInterface::class);

        // A property declared by a parent class whose constructor requires
        // storage (e.g. EntityListBuilder::$storage) is part of an inherited
        // contract; populating it is not the subclass's choice.
        if ($this->isInheritedStorageProperty($node->var, $scope, $storageType)) {
            return [];
        }

        $assignedType = $scope->getType($node->expr);
        // Bail early when assigning literal null — removeNull(NullType) yields
        // NeverType, and isSuperTypeOf(NeverType) is vacuously true for any type.
        if ($assignedType->isNull()->yes()) {
            return [];
        }
        if (!$storageType->isSuperTypeOf(TypeCombinator::removeNull($assignedType))->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Storing entity storage as a class property is not recommended. Call %s::getStorage() at the call-site instead.',
                    EntityTypeManagerInterface::class
                )
            )
                ->identifier('drupal.entityStoragePropertyAssignment')
                ->tip('See https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal')
                ->build(),
        ];
    }

    /**
     * Whether the property is declared by an ancestor that takes storage in its
     * constructor.
     *
     * A storage property declared by any other ancestor is still a choice, and
     * exempting it would let a base class hide the pattern from the rule.
     */
    private function isInheritedStorageProperty(
        Node\Expr\PropertyFetch $propertyFetch,
        Scope $scope,
        ObjectType $storageType
    ): bool {
        if (!$propertyFetch->name instanceof Node\Identifier) {
            return false;
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return false;
        }

        $propertyName = $propertyFetch->name->toString();
        if (!$classReflection->hasInstanceProperty($propertyName)) {
            return false;
        }

        $declaringClass = $classReflection->getInstanceProperty($propertyName, $scope)->getDeclaringClass();
        if ($declaringClass->getName() === $classReflection->getName() || !$declaringClass->hasConstructor()) {
            return false;
        }

        foreach ($declaringClass->getConstructor()->getOnlyVariant()->getParameters() as $parameter) {
            if ($storageType->isSuperTypeOf(TypeCombinator::removeNull($parameter->getType()))->yes()) {
                return true;
            }
        }

        return false;
    }
}
