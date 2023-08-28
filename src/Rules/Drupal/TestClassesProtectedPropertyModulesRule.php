<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\TestCase;

class TestClassesProtectedPropertyModulesRule implements Rule
{

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    /**
     * @throws \PHPStan\ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof ClassPropertyNode);

        if (!$scope->isInClass()) {
            throw new ShouldNotHappenException();
        }

        if ($node->getName() !== 'modules') {
            return [];
        }

        $scopeClassReflection = $scope->getClassReflection();
        if ($scopeClassReflection->isAnonymous()) {
            return [];
        }

        if (!in_array(TestCase::class, $scopeClassReflection->getParentClassesNames(), true)) {
            return [];
        }

        if ($node->isPublic()) {
            return [
                RuleErrorBuilder::message(
                    sprintf('Property %s::$modules property must be protected.', $scopeClassReflection->getDisplayName())
                )->tip('Change record: https://www.drupal.org/node/2909426')->build(),
            ];
        }

        return [];
    }
}
