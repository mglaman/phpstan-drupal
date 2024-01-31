<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\Tests;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\TestCase;

/**
 * Implements rule that all non-abstract test classes name should end with "Test".
 *
 * @implements Rule<Node\Stmt\Class_>
 */
final class TestClassSuffixNameRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Stmt\Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // We're not interested in non-extending classes.
        if ($node->extends === null) {
            return [];
        }

        // We're not interested in abstract classes.
        if ($node->isAbstract()) {
            return [];
        }

        // We need a namespaced class name.
        if ($node->namespacedName === null) {
            return [];
        }

        // We're only interested in \PHPUnit\Framework\TestCase subtype classes.
        $classType = $scope->resolveTypeByName($node->namespacedName);
        $phpUnitFrameworkTestCaseType = new ObjectType(TestCase::class);
        if (!$phpUnitFrameworkTestCaseType->isSuperTypeOf($classType)->yes()) {
            return [];
        }

        // Check class name has suffix "Test".
        // @todo replace this str_ends_with() when php 8 is required.
        if (substr_compare($node->namespacedName->getLast(), 'Test', -4) === 0) {
            return [];
        }

        return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Non-abstract test classes names should always have the suffix "Test", found incorrect class name "%s".',
                        $node->name,
                    )
                )
                ->line($node->getStartLine())
                ->tip('See https://www.drupal.org/docs/develop/standards/php/object-oriented-code#naming')
                ->build()
        ];
    }
}
