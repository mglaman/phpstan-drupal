<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Deprecations\ConditionManagerCreateInstanceContextConfigurationRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class ConditionManagerCreateInstanceContextConfigurationRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new ConditionManagerCreateInstanceContextConfigurationRule();
    }

    public function testRule(): void
    {
        [$version] = explode('.', \Drupal::VERSION, 2);
        if ($version !== '9') {
            self::markTestSkipped('Only tested on Drupal 9.x.x');
        }
        $this->analyse(
            [__DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/phpstan_fixtures.module'],
            [
                [
                    'Passing context values to plugins via configuration is deprecated in drupal:9.1.0 and will be removed before drupal:10.0.0. Instead, call ::setContextValue() on the plugin itself. See https://www.drupal.org/node/3120980',
                    60,
                ]
            ],
        );
    }
}
