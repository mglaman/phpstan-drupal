<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use Nette\Neon\Neon;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_values;
use function basename;
use function class_exists;
use function file_get_contents;
use function glob;
use function is_array;
use function is_string;
use function ltrim;
use function preg_match;
use function sprintf;

/**
 * Enforces "Registering Rules" in AGENTS.md.
 */
final class RuleConventionsTest extends TestCase
{
    private const DEPRECATION_CONDITION = '%deprecationRulesInstalled%';

    /**
     * Rules registered under `rules:` in rules.neon, which can't be turned off.
     *
     * Only rules for code that fails at runtime on every supported Drupal
     * version belong here. Don't add to this list without a reason.
     */
    private const ALWAYS_ON_RULES = [
        'mglaman\PHPStanDrupal\Rules\Drupal\RenderCallbackRule',
        'mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback\RenderCallbackTrustedCallbacksRule',
        'mglaman\PHPStanDrupal\Rules\Drupal\Tests\BrowserTestBaseDefaultThemeRule',
        'mglaman\PHPStanDrupal\Rules\Deprecations\ConfigEntityConfigExportRule',
        'mglaman\PHPStanDrupal\Rules\Drupal\EntityQuery\EntityQueryHasAccessCheckRule',
        'mglaman\PHPStanDrupal\Rules\Drupal\TestClassesProtectedPropertyModulesRule',
    ];

    /**
     * Opt-in rule parameters kept out of bleedingEdge.neon, with the reason.
     */
    private const OPT_IN_RULES_EXCLUDED_FROM_BLEEDING_EDGE = [
        'pluginManagerInspectionRule' => 'Too unreliable to enable for bleeding-edge users.',
    ];

    /**
     * Rules in src/Rules/Deprecations that don't use the deprecation condition.
     */
    private const DEPRECATIONS_NAMESPACE_EXCEPTIONS = [
        'mglaman\PHPStanDrupal\Rules\Deprecations\ConfigEntityConfigExportRule' => 'A missing config_export key throws at runtime, so it is always on.',
        'mglaman\PHPStanDrupal\Rules\Deprecations\DeprecatedHookImplementation' => 'Not registered. See https://github.com/mglaman/phpstan-drupal/issues/963.',
    ];

    public function testAlwaysOnRulesMatchList(): void
    {
        $rulesNeon = self::decodeNeonFile('rules.neon');
        self::assertIsArray($rulesNeon['rules'] ?? null, 'rules.neon must have a rules: section.');

        $registeredClasses = [];
        foreach ($rulesNeon['rules'] as $class) {
            self::assertIsString($class);
            $registeredClasses[] = ltrim($class, '\\');
            self::assertContains(
                ltrim($class, '\\'),
                self::ALWAYS_ON_RULES,
                sprintf(
                    '%s is registered under rules: in rules.neon, so it can never be turned off. Register it under conditionalTags instead. See "Registering Rules" in AGENTS.md.',
                    $class
                )
            );
        }

        self::assertSame(
            [],
            array_values(array_diff(self::ALWAYS_ON_RULES, $registeredClasses)),
            'These ALWAYS_ON_RULES entries are no longer under rules: in rules.neon. Remove them from the list.'
        );
    }

    public function testExceptionListsMatchExistingRules(): void
    {
        $ruleDefaults = self::extensionRuleDefaults();
        foreach (array_keys(self::OPT_IN_RULES_EXCLUDED_FROM_BLEEDING_EDGE) as $parameter) {
            self::assertFalse(
                $ruleDefaults[$parameter] ?? null,
                sprintf(
                    'OPT_IN_RULES_EXCLUDED_FROM_BLEEDING_EDGE lists drupal.rules.%s, but it is not an opt-in rule in extension.neon. Remove it from the list.',
                    $parameter
                )
            );
        }

        $deprecationsNamespaceRuleClasses = self::deprecationsNamespaceRuleClasses();
        foreach (array_keys(self::DEPRECATIONS_NAMESPACE_EXCEPTIONS) as $class) {
            self::assertContains(
                $class,
                $deprecationsNamespaceRuleClasses,
                sprintf(
                    'DEPRECATIONS_NAMESPACE_EXCEPTIONS lists %s, but it is not a rule in src/Rules/Deprecations. Remove it from the list.',
                    $class
                )
            );
        }
    }

    public function testConditionalRulesUseKnownConditions(): void
    {
        $ruleDefaults = self::extensionRuleDefaults();

        foreach (self::conditionalRuleConditions() as $class => $condition) {
            if ($condition === self::DEPRECATION_CONDITION) {
                continue;
            }
            $parameter = self::ruleParameterName($condition);
            self::assertNotNull(
                $parameter,
                sprintf(
                    '%s uses the condition "%s". Use %%drupal.rules.<name>%% or %s. See "Registering Rules" in AGENTS.md.',
                    $class,
                    $condition,
                    self::DEPRECATION_CONDITION
                )
            );
            self::assertArrayHasKey(
                $parameter,
                $ruleDefaults,
                sprintf('%s uses drupal.rules.%s, but extension.neon has no default for it.', $class, $parameter)
            );
            self::assertIsBool(
                $ruleDefaults[$parameter],
                sprintf('The default for drupal.rules.%s in extension.neon must be true or false.', $parameter)
            );
        }
    }

    public function testOptInRulesRunInBleedingEdge(): void
    {
        $bleedingEdgeRules = self::bleedingEdgeRules();

        foreach (self::extensionRuleDefaults() as $parameter => $enabledByDefault) {
            if ($enabledByDefault === true) {
                self::assertArrayNotHasKey(
                    $parameter,
                    $bleedingEdgeRules,
                    sprintf('drupal.rules.%s is on by default, so remove it from bleedingEdge.neon.', $parameter)
                );
                continue;
            }

            if (array_key_exists($parameter, self::OPT_IN_RULES_EXCLUDED_FROM_BLEEDING_EDGE)) {
                self::assertArrayNotHasKey(
                    $parameter,
                    $bleedingEdgeRules,
                    sprintf(
                        'drupal.rules.%s is listed in OPT_IN_RULES_EXCLUDED_FROM_BLEEDING_EDGE but is enabled in bleedingEdge.neon. Remove it from one of them.',
                        $parameter
                    )
                );
                continue;
            }

            self::assertTrue(
                $bleedingEdgeRules[$parameter] ?? false,
                sprintf(
                    'drupal.rules.%s is opt-in, so bleedingEdge.neon must set it to true. See "Registering Rules" in AGENTS.md.',
                    $parameter
                )
            );
        }
    }

    public function testDeprecationRulesUseDeprecationCondition(): void
    {
        $conditions = self::conditionalRuleConditions();

        foreach (self::deprecationsNamespaceRuleClasses() as $class) {
            if (array_key_exists($class, self::DEPRECATIONS_NAMESPACE_EXCEPTIONS)) {
                continue;
            }
            self::assertSame(
                self::DEPRECATION_CONDITION,
                $conditions[$class] ?? null,
                sprintf(
                    '%s is a deprecation rule, so register it under conditionalTags with %s. See "Registering Rules" in AGENTS.md.',
                    $class,
                    self::DEPRECATION_CONDITION
                )
            );
        }
    }

    /**
     * @return array<string, string>
     *   The `phpstan.rules.rule` condition of each rule class in conditionalTags.
     */
    private static function conditionalRuleConditions(): array
    {
        $rulesNeon = self::decodeNeonFile('rules.neon');
        self::assertIsArray($rulesNeon['conditionalTags'] ?? null, 'rules.neon must have a conditionalTags: section.');

        $conditions = [];
        foreach ($rulesNeon['conditionalTags'] as $class => $tags) {
            if (!is_array($tags) || !is_string($tags['phpstan.rules.rule'] ?? null)) {
                continue;
            }
            $conditions[ltrim((string) $class, '\\')] = $tags['phpstan.rules.rule'];
        }
        return $conditions;
    }

    private static function ruleParameterName(string $condition): ?string
    {
        return preg_match('/^%drupal\.rules\.([A-Za-z0-9_]+)%$/', $condition, $matches) === 1 ? $matches[1] : null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function extensionRuleDefaults(): array
    {
        $ruleDefaults = self::decodeNeonFile('extension.neon')['parameters']['drupal']['rules'] ?? null;
        self::assertIsArray($ruleDefaults, 'extension.neon must define parameters.drupal.rules.');
        return $ruleDefaults;
    }

    /**
     * @return array<string, mixed>
     */
    private static function bleedingEdgeRules(): array
    {
        $bleedingEdgeRules = self::decodeNeonFile('bleedingEdge.neon')['parameters']['drupal']['rules'] ?? [];
        self::assertIsArray($bleedingEdgeRules);
        return $bleedingEdgeRules;
    }

    /**
     * @return list<class-string>
     */
    private static function deprecationsNamespaceRuleClasses(): array
    {
        $files = glob(__DIR__ . '/../../src/Rules/Deprecations/*.php');
        self::assertNotFalse($files);

        $classes = [];
        foreach ($files as $file) {
            $class = 'mglaman\PHPStanDrupal\Rules\Deprecations\\' . basename($file, '.php');
            self::assertTrue(class_exists($class), sprintf('%s does not declare %s.', $file, $class));
            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || !$reflection->implementsInterface(Rule::class)) {
                continue;
            }
            $classes[] = $class;
        }
        self::assertNotEmpty($classes);
        return $classes;
    }

    /**
     * @return array<mixed>
     */
    private static function decodeNeonFile(string $name): array
    {
        $contents = file_get_contents(__DIR__ . '/../../' . $name);
        self::assertIsString($contents, sprintf('Could not read %s.', $name));
        $decoded = Neon::decode($contents);
        self::assertIsArray($decoded, sprintf('%s did not decode to an array.', $name));
        return $decoded;
    }
}
