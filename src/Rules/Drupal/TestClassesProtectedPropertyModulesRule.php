<?php

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Reflection\ClassReflection;
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

        $scopeClassReflection = $scope->getClassReflection();

        if (!$this->extendsPHPUnitFrameworkTestCase($scopeClassReflection)) {
            return [];
        }

        if ($node->getName() !== 'modules') {
            return [];
        }

        if ($node->isPublic()) {
            return [
                RuleErrorBuilder::message(
                    sprintf('Declaring the ::$modules property as non-protected in %s is required.', $scopeClassReflection->getName())
                )->tip('Change record: https://www.drupal.org/node/2909426')->build(),
            ];
        }

        return [];
    }

    /**
     * Checks if the given class reflection extends PHPUnit's TestCase.
     *
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     *   The class reflection to check.
     *
     * @return bool
     *   True, if the given class reflection extends PPHPUnit's TestCase, false
     *   otherwise.
     */
    protected function extendsPHPUnitFrameworkTestCase(ClassReflection $classReflection): bool
    {
        return
            !$classReflection->isAnonymous()
            && in_array(TestCase::class, $classReflection->getParentClassesNames(), true);
    }
}
