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
     *
     * @param list<array{0: string, 1: int, 2?: string|null}> $errorMessages
     */
    public function testContextAnnotationRuleCheck(string $path, array $errorMessages): void
    {
        $this->analyse([$path] , $errorMessages);
    }

    public static function pluginData(): \Generator
    {
        yield [
            __DIR__ . '/data/plugin-condition-context-deprecated.php',
            [
                [
                    'Providing context definitions via the "context" key is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use the "context_definitions" key instead.',
                    17
                ],
            ]
        ];
        yield [
            __DIR__ . '/data/plugin-condition-context-definitions.php',
            []
        ];
        yield [
            __DIR__ . '/data/plugin-action-sample.php',
            []
        ];
        yield [
            __DIR__ . '/data/plugin-block-context-deprecated.php',
            [
                [
                    'Providing context definitions via the "context" key is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use the "context_definitions" key instead.',
                    20
                ],
            ]
        ];
    }


}
