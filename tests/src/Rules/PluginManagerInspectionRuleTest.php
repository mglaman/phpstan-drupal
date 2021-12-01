<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Classes\PluginManagerInspectionRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class PluginManagerInspectionRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new PluginManagerInspectionRule(
            $this->createReflectionProvider()
        );
    }

    /**
     * @dataProvider pluginManagerData
     */
    public function testRule(string $path, array $errorMessages): void
    {
        $this->analyse([$path], $errorMessages);
    }

    public function pluginManagerData(): \Generator
    {
        yield 'BreakpointManager' => [
            __DIR__ . '/../../fixtures/drupal/core/modules/breakpoint/src/BreakpointManager.php',
            []
        ];
        yield 'ExamplePluginManager' => [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/ExamplePluginManager.php',
            []
        ];
    }

}
