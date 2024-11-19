<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\Tests;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPUnit\Framework\Test;
use function count;
use function in_array;
use function interface_exists;
use function substr_compare;

/**
 * @implements Rule<Node\Stmt\Class_>
 */
final class BrowserTestBaseDefaultThemeRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Stmt\Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!interface_exists(Test::class)) {
            return [];
        }
        if ($node->extends === null) {
            return [];
        }
        if ($node->namespacedName === null) {
            return [];
        }

        // Only inspect tests.
        // @todo replace this str_ends_with() when php 8 is required.
        if (0 !== substr_compare($node->namespacedName->getLast(), 'Test', -4)) {
            return [];
        }

        // Do some cheap preflight tests to make sure the class is in a
        // namespace that makes sense to inspect.
        $parts = $node->namespacedName->getParts();
        // The namespace is too short to be a test so skip inspection.
        if (count($parts) < 3) {
            return [];
        }
        // If the 4th component matches it's a module test. If the 2nd, core.
        if ($parts[3] !== 'Functional'
            && $parts [3] !== 'FunctionalJavascript'
            && $parts[1] !== 'FunctionalTests'
            && $parts[1] !== 'FunctionalJavascriptTests') {
            return [];
        }


        $classType = $scope->resolveTypeByName($node->namespacedName);
        assert($classType instanceof ObjectType);

        $browserTestBaseType = new ObjectType('Drupal\\Tests\\BrowserTestBase');
        if (!$browserTestBaseType->isSuperTypeOf($classType)->yes()) {
            return [];
        }

        $excludedTestTypes = TypeCombinator::union(
            new ObjectType('Drupal\\FunctionalTests\\Update\\UpdatePathTestBase'),
            new ObjectType('Drupal\\FunctionalTests\\Installer\\InstallerConfigDirectoryTestBase'),
            new ObjectType('Drupal\\FunctionalTests\\Installer\\InstallerExistingConfigTestBase')
        );
        if ($excludedTestTypes->isSuperTypeOf($classType)->yes()) {
            return [];
        }

        $reflection = $classType->getClassReflection();
        assert($reflection !== null);
        if ($reflection->isAbstract()) {
            return [];
        }
        $defaultProperties = $reflection->getNativeReflection()->getDefaultProperties();
        $profile = $defaultProperties['profile'] ?? null;

        $testingProfilesWithoutThemes = [
            'testing',
            'nightwatch_testing',
            'testing_config_overrides',
            'testing_missing_dependencies',
            'testing_multilingual',
            'testing_multilingual_with_english',
            'testing_requirements',
        ];
        if ($profile !== null && !in_array($profile, $testingProfilesWithoutThemes, true)) {
            return [];
        }

        $defaultTheme = $defaultProperties['defaultTheme'] ?? null;

        if ($defaultTheme === null || $defaultTheme === '') {
            return [
                RuleErrorBuilder::message('Drupal\Tests\BrowserTestBase::$defaultTheme is required. See https://www.drupal.org/node/3083055, which includes recommendations on which theme to use.')
                    ->line($node->getStartLine())->build(),
            ];
        }
        return [];
    }
}
