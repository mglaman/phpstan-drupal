<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Classes\PluginManagerInspectionRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class PluginManagerInspectionRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new PluginManagerInspectionRule(
            self::createReflectionProvider()
        );
    }

    /**
     * @dataProvider pluginManagerData
     */
    public function testRule(string $path, array $errorMessages): void
    {
        $this->analyse([$path], $errorMessages);
    }

    public static function pluginManagerData(): \Generator
    {
        yield 'BreakpointManager' => [
            __DIR__ . '/../../fixtures/drupal/core/modules/breakpoint/src/BreakpointManager.php',
            []
        ];
        yield 'ExamplePluginManager' => [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/ExamplePluginManager.php',
            []
        ];
        yield [
            __DIR__ . '/data/plugin-manager-alter-info.php',
            [
                [
                    'Plugin managers must call alterInfo to allow plugin definitions to be altered.',
                    9,
                    'For example, to invoke hook_mymodule_data_alter() call alterInfo with "mymodule_data".'
                ],
                [
                    'Plugin managers must call alterInfo to allow plugin definitions to be altered.',
                    41,
                    'For example, to invoke hook_mymodule_data_alter() call alterInfo with "mymodule_data".'
                ],
            ]
        ];
    }

}
