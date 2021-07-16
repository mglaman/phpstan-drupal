<?php declare(strict_types=1);

namespace PHPStan\Drupal\Rules;

use PHPStan\Drupal\AnalyzerTestBase;

final class PluginAnnotationContextDefinitionsRuleTest extends AnalyzerTestBase {

    /**
     * @dataProvider pluginData
     */
    public function testContextAnnotationRuleCheck(string $path, int $count, array $errorMessages): void
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
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Plugin/Condition/ConditionWithContext.php',
            1,
            [
                'Providing context definitions via the "context" key is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use the "context_definitions" key instead.',
            ]
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Plugin/Condition/ConditionWithContextDefinitions.php',
            0,
            []
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Plugin/Action/ActionSample.php',
            0,
            []
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Plugin/Block/BlockWithContext.php',
            1,
            [
                'Providing context definitions via the "context" key is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use the "context_definitions" key instead.',
            ]
        ];
    }


}
