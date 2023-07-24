<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\PluginManager\PluginManagerSetsCacheBackendRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class PluginManagerSetsCacheBackendRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new PluginManagerSetsCacheBackendRule();
    }

    /**
     * @dataProvider ruleData
     */
    public function testRule(string $path, array $errorMessages): void
    {
        $this->analyse([$path], $errorMessages);
    }

    public static function ruleData(): \Generator
    {
        yield [
            __DIR__ . '/data/plugin-manager-cache-backend.php',
            [
                [
                    'Missing cache backend declaration for performance.',
                    12
                ]
            ]
        ];
    }
}
