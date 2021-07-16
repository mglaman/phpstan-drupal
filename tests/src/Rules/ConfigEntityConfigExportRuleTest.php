<?php declare(strict_types=1);

namespace PHPStan\Drupal\Rules;

use PHPStan\Drupal\AnalyzerTestBase;

final class ConfigEntityConfigExportRuleTest extends AnalyzerTestBase {

    /**
     * @dataProvider pluginData
     */
    public function testConfigExportRuleCheck(string $path, int $count, array $errorMessages): void
    {
        $errors = $this->runAnalyze($path);
        self::assertCount($count, $errors->getErrors(), var_export($errors, true));
        foreach ($errors->getErrors() as $key => $error) {
            self::assertEquals($errorMessages[$key], $error->getMessage());
        }
    }

    public function pluginData(): \Generator
    {
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Entity/ConfigWithoutExport.php',
            1,
            [
                'Configuration entity must define a `config_export` key. See https://www.drupal.org/node/2481909',
            ]
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Entity/ConfigWithExport.php',
            0,
            []
        ];
    }


}
