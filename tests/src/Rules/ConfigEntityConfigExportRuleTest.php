<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Deprecations\ConfigEntityConfigExportRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class ConfigEntityConfigExportRuleTest extends DrupalRuleTestCase {

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new ConfigEntityConfigExportRule(
            $this->createReflectionProvider()
        );
    }

    /**
     * @dataProvider pluginData
     *
     * @param list<array{0: string, 1: int, 2?: string|null}> $errorMessages
     */
    public function testConfigExportRuleCheck(string $path, array $errorMessages): void
    {
        $this->analyse([$path], $errorMessages);
    }

    public static function pluginData(): \Generator
    {
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Entity/ConfigWithoutExport.php',
            [
               [
                   'Configuration entity must define a `config_export` key. See https://www.drupal.org/node/2481909',
                   15
               ],
            ]
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Entity/ConfigWithExport.php',
            []
        ];
    }


}
