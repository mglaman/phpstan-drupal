<?php

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\ShouldNotHappenException;

class EnhancedRequireParentConstructCallRule extends RequireParentConstructCallRule
{

    /**
     * @param Node $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return string[]
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Stmt\ClassMethod);

        if (!$scope->isInClass()) {
            throw new ShouldNotHappenException();
        }

        if ($scope->isInTrait()) {
            return [];
        }

        if ($node->name->name !== '__construct') {
            return [];
        }

        $scopeClassReflection = $scope->getClassReflection();

        // Provides specific handling for Drupal instances where not calling the parent __construct is "okay."
        if ($scopeClassReflection === null) {
            throw new ShouldNotHappenException();
        }
        $classReflection = $scopeClassReflection->getNativeReflection();
        if (!$classReflection->isInterface()
            && !$classReflection->isAnonymous()
            && $classReflection->implementsInterface('Drupal\Component\Plugin\PluginManagerInterface')
        ) {
            return [];
        }

        return parent::processNode($node, $scope);
    }
}
