<?php declare(strict_types=1);

namespace PHPStan\Rules\Drupal;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class GlobalDrupalDependencyInjectionRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\StaticCall);

        // Only check static calls to \Drupal
        if (!($node->class instanceof Node\Name\FullyQualified) || (string) $node->class !== 'Drupal') {
            return [];
        }
        if (!$scope->isInClass() && !$scope->isInTrait()) {
            return [];
        }

        $whitelist = [
            // The classes in the typed data system cannot use dependency injection.
            'Drupal\Core\TypedData\TypedDataInterface',
        ];
        $classReflection = $scope->getClassReflection()->getNativeReflection();
        foreach ($whitelist as $item) {
            if ($classReflection->implementsInterface($item)) {
                return [];
            }
        }

        if ($scope->getFunctionName() === null) {
            throw new \PHPStan\ShouldNotHappenException();
        }
        // Static methods have to invoke \Drupal.
        if ($scope->getFunction()->isStatic()) {
            return [];
        }

        return [
            '\Drupal calls should be avoided in classes, use dependency injection instead'
        ];

    }

}
