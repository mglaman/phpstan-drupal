<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Deprecations\PluginAnnotationContextDefinitionsRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class PluginAnnotationContextDefinitionsRuleTest extends DrupalRuleTestCase {

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new PluginAnnotationContextDefinitionsRule(
            $this->createReflectionProvider()
        );
    }

    /**
     * @dataProvider pluginData
     */
    public function testContextAnnotationRuleCheck(string $path, array $errorMessages): void
    {
        $this->analyse([$path] , $errorMessages);
    }

    public static function pluginData(): \Generator
    {
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Plugin/Condition/ConditionWithContext.php',
            [
                [
                    'Providing context definitions via the "context" key is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use the "context_definitions" key instead.',
                    17
                ],
            ]
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Plugin/Condition/ConditionWithContextDefinitions.php',
            []
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Plugin/Action/ActionSample.php',
            []
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Plugin/Block/BlockWithContext.php',
            [
                [
                    'Providing context definitions via the "context" key is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use the "context_definitions" key instead.',
                    20
                ],
            ]
        ];
    }


}
