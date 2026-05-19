<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use Nette\Neon\Neon;
use PHPUnit\Framework\TestCase;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function file_get_contents;
use function in_array;
use function is_array;
use function ltrim;
use function preg_match;
use function sprintf;

/**
 * Enforces the rule registration conventions documented in CLAUDE.md.
 *
 * Every rule must be toggleable: registered through `conditionalTags` with a
 * matching boolean parameter under `parameters.drupal.rules` in
 * `extension.neon`. A rule is opt-in while its default is `false` (and must
 * then be enabled in `bleedingEdge.neon`) and graduates to the default ruleset
 * by flipping that default to `true`. Direct `rules:` registration is reserved
 * for the legacy rules that predate this convention and must not grow.
 */
final class RuleConventionsTest extends TestCase
{

    /**
     * Legacy rules registered directly under `rules:` in rules.neon.
     *
     * These predate the toggleable-rule convention and are not opt-out. This
     * list must never grow: new rules belong in `conditionalTags`.
     *
     * @var list<string>
     */
    private const LEGACY_DIRECT_RULES = [
        'mglaman\PHPStanDrupal\Rules\Drupal\Coder\DiscouragedFunctionsRule',
        'mglaman\PHPStanDrupal\Rules\Drupal\GlobalDrupalDependencyInjectionRule',
        'mglaman\PHPStanDrupal\Rules\Drupal\PluginManager\PluginManagerSetsCacheBackendRule',
        'mglaman\PHPStanDrupal\Rules\Drupal\RenderCallbackRule',
        'mglaman\PHPStanDrupal\Rules\Deprecations\StaticServiceDeprecatedServiceRule',
        'mglaman\PHPStanDrupal\Rules\Deprecations\GetDeprecatedServiceRule',
        'mglaman\PHPStanDrupal\Rules\Drupal\Tests\BrowserTestBaseDefaultThemeRule',
        'mglaman\PHPStanDrupal\Rules\Deprecations\ConfigEntityConfigExportRule',
        'mglaman\PHPStanDrupal\Rules\Deprecations\PluginAnnotationContextDefinitionsRule',
        'mglaman\PHPStanDrupal\Rules\Drupal\ModuleLoadInclude',
        'mglaman\PHPStanDrupal\Rules\Drupal\LoadIncludes',
        'mglaman\PHPStanDrupal\Rules\Drupal\EntityQuery\EntityQueryHasAccessCheckRule',
        'mglaman\PHPStanDrupal\Rules\Drupal\TestClassesProtectedPropertyModulesRule',
    ];

    /**
     * Conditional rule parameters that have graduated to the default ruleset.
     *
     * A graduated rule keeps its `conditionalTags` registration (so it stays
     * opt-out) but its `extension.neon` default is `true`. Graduating a rule is
     * a deliberate act: add its parameter name here in the same change that
     * flips the default to `true`. Graduated rules do not belong in
     * `bleedingEdge.neon` because they are already on by default.
     *
     * @var list<string>
     */
    private const GRADUATED_RULES = [
        'classExtendsInternalClassRule',
    ];

    /**
     * Opt-in rules deliberately kept out of bleedingEdge.neon.
     *
     * Normally an opt-in rule (default `false`) must also be enabled in
     * bleedingEdge.neon. A rule may be excluded here when it is known to be too
     * noisy or unstable to inflict on bleeding-edge users; it then stays
     * available only through explicit per-rule configuration. The path back is
     * to remove it from this list and add it to bleedingEdge.neon.
     *
     * @var list<string>
     */
    private const OPT_IN_RULES_EXCLUDED_FROM_BLEEDING_EDGE = [
        // PluginManagerInspectionRule is currently too unreliable to enable by
        // default for bleeding-edge users.
        'pluginManagerInspectionRule',
    ];

    public function testNewRulesAreNotDirectlyRegistered(): void
    {
        $rules = $this->decodeFile('rules.neon');
        self::assertArrayHasKey('rules', $rules, 'rules.neon must define a rules: section.');
        self::assertIsArray($rules['rules']);

        $allowlist = array_flip(self::LEGACY_DIRECT_RULES);
        foreach ($rules['rules'] as $class) {
            $normalized = ltrim((string) $class, '\\');
            self::assertArrayHasKey(
                $normalized,
                $allowlist,
                sprintf(
                    "%s is registered directly under rules: in rules.neon but is not a known legacy rule.\n"
                    . 'New rules must be toggleable: register the rule under conditionalTags with a '
                    . '%%drupal.rules.<name>%% parameter and add a `false` default under '
                    . 'parameters.drupal.rules in extension.neon. See CLAUDE.md.',
                    $normalized
                )
            );
        }
    }

    public function testConditionalRulesHaveFalseDefaultInExtensionNeon(): void
    {
        $defaults = $this->extensionRuleDefaults();

        foreach ($this->conditionalRuleParameters() as $parameter) {
            self::assertArrayHasKey(
                $parameter,
                $defaults,
                sprintf(
                    'Conditional rule parameter "%s" (from rules.neon conditionalTags) has no default '
                    . 'under parameters.drupal.rules in extension.neon. Add it with a `false` default '
                    . 'so the rule is opt-in and toggleable. See CLAUDE.md.',
                    $parameter
                )
            );

            $default = $defaults[$parameter];
            self::assertIsBool(
                $default,
                sprintf('Default for rule parameter "%s" in extension.neon must be a boolean.', $parameter)
            );

            if (in_array($parameter, self::GRADUATED_RULES, true)) {
                self::assertTrue(
                    $default,
                    sprintf(
                        'Rule parameter "%s" is listed as graduated but its extension.neon default is not '
                        . '`true`. Either flip the default to `true` or remove it from GRADUATED_RULES.',
                        $parameter
                    )
                );
                continue;
            }

            self::assertFalse(
                $default,
                sprintf(
                    'Rule parameter "%s" must default to `false` in extension.neon (opt-in). To graduate it '
                    . 'to the default ruleset, flip the default to `true` and add "%s" to '
                    . 'RuleConventionsTest::GRADUATED_RULES in the same change. See CLAUDE.md.',
                    $parameter,
                    $parameter
                )
            );
        }
    }

    public function testOptInRulesAreEnabledInBleedingEdge(): void
    {
        $defaults = $this->extensionRuleDefaults();
        $bleedingEdge = $this->bleedingEdgeRuleOverrides();

        foreach ($this->conditionalRuleParameters() as $parameter) {
            if (!array_key_exists($parameter, $defaults) || $defaults[$parameter] !== false) {
                // Graduated rules (default `true`) are already on by default and
                // must not be listed in bleedingEdge.neon.
                continue;
            }

            if (in_array($parameter, self::OPT_IN_RULES_EXCLUDED_FROM_BLEEDING_EDGE, true)) {
                self::assertArrayNotHasKey(
                    $parameter,
                    $bleedingEdge,
                    sprintf(
                        'Rule "%s" is listed in OPT_IN_RULES_EXCLUDED_FROM_BLEEDING_EDGE but is enabled in '
                        . 'bleedingEdge.neon. Remove it from one or the other so the exception stays honest.',
                        $parameter
                    )
                );
                continue;
            }

            self::assertArrayHasKey(
                $parameter,
                $bleedingEdge,
                sprintf(
                    'Opt-in rule parameter "%s" (default `false` in extension.neon) is not enabled in '
                    . 'bleedingEdge.neon. Add `%s: true` under parameters.drupal.rules in bleedingEdge.neon '
                    . 'so the rule runs for bleeding-edge users. See CLAUDE.md.',
                    $parameter,
                    $parameter
                )
            );
            self::assertTrue(
                $bleedingEdge[$parameter],
                sprintf('Rule parameter "%s" must be set to `true` in bleedingEdge.neon.', $parameter)
            );
        }
    }

    /**
     * Resolves the unique set of `phpstan.rules.rule` parameter names from
     * the conditionalTags section of rules.neon.
     *
     * @return list<string>
     */
    private function conditionalRuleParameters(): array
    {
        $rules = $this->decodeFile('rules.neon');
        self::assertArrayHasKey('conditionalTags', $rules, 'rules.neon must define a conditionalTags: section.');
        self::assertIsArray($rules['conditionalTags']);

        $parameters = [];
        foreach ($rules['conditionalTags'] as $tags) {
            if (!is_array($tags) || !array_key_exists('phpstan.rules.rule', $tags)) {
                continue;
            }
            $tagValue = (string) $tags['phpstan.rules.rule'];
            self::assertSame(
                1,
                preg_match('/^%drupal\.rules\.([A-Za-z0-9_]+)%$/', $tagValue, $matches),
                sprintf(
                    'conditionalTags phpstan.rules.rule value "%s" must be a %%drupal.rules.<name>%% parameter.',
                    $tagValue
                )
            );
            $parameters[$matches[1]] = true;
        }

        self::assertNotEmpty($parameters, 'Expected at least one conditional rule in rules.neon.');
        return array_keys($parameters);
    }

    /**
     * @return array<string, mixed>
     */
    private function extensionRuleDefaults(): array
    {
        $extension = $this->decodeFile('extension.neon');
        $rules = $extension['parameters']['drupal']['rules'] ?? null;
        self::assertIsArray($rules, 'extension.neon must define parameters.drupal.rules.');
        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function bleedingEdgeRuleOverrides(): array
    {
        $bleedingEdge = $this->decodeFile('bleedingEdge.neon');
        $rules = $bleedingEdge['parameters']['drupal']['rules'] ?? null;
        self::assertIsArray($rules, 'bleedingEdge.neon must define parameters.drupal.rules.');
        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeFile(string $name): array
    {
        $path = __DIR__ . '/../../' . $name;
        self::assertFileExists($path);
        $decoded = Neon::decode((string) file_get_contents($path));
        self::assertIsArray($decoded, sprintf('%s did not decode to an array.', $name));
        return $decoded;
    }
}
