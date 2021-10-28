<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use mglaman\PHPStanDrupal\Rules\Deprecations\GetDeprecatedServiceRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class GetDeprecatedServiceRuleTest extends DrupalRuleTestCase {

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new GetDeprecatedServiceRule(
            self::getContainer()->getByType(ServiceMap::class)
        );
    }

    /**
     * @dataProvider drupal8Data
     */
    public function testRuleDrupal8(string $path, array $errorMessages): void
    {
        if (version_compare('9.0.0', \Drupal::VERSION) !== 1) {
            self::markTestSkipped('Only tested on Drupal 8.x.x');
        }
        $this->analyse([$path], $errorMessages);
    }

    /**
     * @dataProvider drupal9Data
     */
    public function testRuleDrupal9(string $path, array $errorMessages): void
    {
        if (version_compare(\Drupal::VERSION, '9.0.0', 'gt') === 1) {
            self::markTestSkipped('Only tested on Drupal 9.x.x');
        }
        $this->analyse([$path], $errorMessages);
    }

    public function drupal8Data(): \Generator
    {
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Plugin/Block/EntityManagerInjectedBlock.php',
            [
                [
                    'The "entity.manager" service is deprecated. You should use the \'entity_type.manager\' service instead.',
                    15
                ],
            ]
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/Controller/EntityManagerInjectedController.php',
            [
                [
                    'The "entity.manager" service is deprecated. You should use the \'entity_type.manager\' service instead.',
                    15
                ],
            ]
        ];
    }

    public function drupal9Data(): \Generator
    {
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/phpstan_fixtures.module',
            [
                [
                    'The "app.root" service is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Use the app.root parameter instead. See https://www.drupal.org/node/3080612',
                    18
                ],
            ]
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/AppRootParameter.php',
            [
                [
                    'The "app.root" service is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Use the app.root parameter instead. See https://www.drupal.org/node/3080612',
                    17
                ],
            ]
        ];
    }


}
