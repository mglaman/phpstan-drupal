<?php

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

class EnhancedRequireParentConstructCallRule extends RequireParentConstructCallRule {

    /**
     * @param Node $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return string[]
     * @throws \PHPStan\ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Stmt\ClassMethod);

        if (!$scope->isInClass()) {
            throw new \PHPStan\ShouldNotHappenException();
        }

        if ($scope->isInTrait()) {
            return [];
        }

        if ($node->name->name !== '__construct') {
            return [];
        }

        // Provides specific handling for Drupal instances where not calling the parent __construct is "okay."
        $classReflection = $scope->getClassReflection()->getNativeReflection();
        if (!$classReflection->isInterface() && !$classReflection->isAnonymous() && $classReflection->implementsInterface('Drupal\Component\Plugin\PluginManagerInterface')) {
            // @todo Add Rule to check plugin manager classes for __construct check if non-YAML and other inspections.
            return [];
        }

        return parent::processNode($node, $scope);
    }


}
