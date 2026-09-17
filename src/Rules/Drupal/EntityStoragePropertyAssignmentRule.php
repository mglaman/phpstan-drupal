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

        // A property declared by a parent class (e.g. EntityListBuilder::$storage)
        // is part of an inherited contract; populating it is not the subclass's
        // choice, so only report properties the class declares itself.
        if ($this->isInheritedProperty($node->var, $scope)) {
            return [];
        }

        $assignedType = $scope->getType($node->expr);
        // Bail early when assigning literal null — removeNull(NullType) yields
        // NeverType, and isSuperTypeOf(NeverType) is vacuously true for any type.
        if ($assignedType->isNull()->yes()) {
            return [];
        }
        $storageType = new ObjectType(EntityStorageInterface::class);
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

    private function isInheritedProperty(Node\Expr\PropertyFetch $propertyFetch, Scope $scope): bool
    {
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
        return $declaringClass->getName() !== $classReflection->getName();
    }
}
