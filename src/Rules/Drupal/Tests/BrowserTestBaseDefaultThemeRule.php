<?php declare(strict_types=1);

namespace PHPStan\Rules\Drupal\Tests;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\ObjectType;

final class BrowserTestBaseDefaultThemeRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Stmt\Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Stmt\Class_);
        if ($node->extends === null) {
            return [];
        }
        if ($node->namespacedName === null) {
            return [];
        }

        $classType = $scope->resolveTypeByName($node->namespacedName);
        assert($classType instanceof ObjectType);
        $browserTestBaseAncestor = $classType->getAncestorWithClassName('Drupal\\Tests\\BrowserTestBase');
        if ($browserTestBaseAncestor === null) {
            return [];
        }

        $reflection = $classType->getClassReflection();
        assert($reflection !== null);
        $defaultProperties = $reflection->getNativeReflection()->getDefaultProperties();
        $defaultTheme = $defaultProperties['defaultTheme'] ?? null;

        if ($defaultTheme === null || $defaultTheme === '') {
            return [
                'Drupal\Tests\BrowserTestBase::$defaultTheme is required. See https://www.drupal.org/node/3083055, which includes recommendations on which theme to use.',
            ];
        }
        return [];
    }
}
