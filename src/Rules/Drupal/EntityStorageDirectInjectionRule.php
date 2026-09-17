<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

/**
 * @implements Rule<Node\Stmt\ClassMethod>
 */
final class EntityStorageDirectInjectionRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Stmt\ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name->toString() !== '__construct') {
            return [];
        }
        if (!$scope->isInClass()) {
            return [];
        }

        $storageType = new ObjectType(EntityStorageInterface::class);

        // A subclass overriding a constructor that already requires entity
        // storage (e.g. EntityListBuilder::__construct()) has to accept and
        // forward it. Those parameters are an inherited obligation, not a
        // choice, so the first N storage parameters are skipped where N is the
        // number the parent constructor declares. Any further storage
        // parameter is the subclass's own addition and is reported.
        $inheritedStorageParams = $this->countParentConstructorStorageParams($scope->getClassReflection(), $storageType);

        $errors = [];

        foreach ($node->params as $param) {
            if ($param->type === null) {
                continue;
            }

            $paramType = $scope->getFunctionType($param->type, false, false);
            if (!$storageType->isSuperTypeOf(TypeCombinator::removeNull($paramType))->yes()) {
                continue;
            }

            if ($inheritedStorageParams > 0) {
                $inheritedStorageParams--;
                continue;
            }

            $paramName = $param->var instanceof Variable && is_string($param->var->name)
                ? '$' . $param->var->name
                : 'parameter';

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Direct injection of entity storage via %s is not recommended. Inject %s and call getStorage() at the call-site instead.',
                    $paramName,
                    EntityTypeManagerInterface::class
                )
            )
                ->line($param->getStartLine())
                ->identifier('drupal.entityStorageDirectInjection')
                ->tip('See https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal')
                ->build();
        }

        return $errors;
    }

    /**
     * Counts the entity storage parameters of the nearest inherited constructor.
     *
     * Only the constructor the subclass actually overrides counts: if an
     * intermediate class replaced the storage parameter with something else,
     * re-introducing storage further down is a choice again.
     */
    private function countParentConstructorStorageParams(ClassReflection $classReflection, ObjectType $storageType): int
    {
        $parentClass = $classReflection->getParentClass();
        if ($parentClass === null || !$parentClass->hasConstructor()) {
            return 0;
        }

        $count = 0;
        foreach ($parentClass->getConstructor()->getOnlyVariant()->getParameters() as $parameter) {
            if ($storageType->isSuperTypeOf(TypeCombinator::removeNull($parameter->getType()))->yes()) {
                $count++;
            }
        }

        return $count;
    }
}
