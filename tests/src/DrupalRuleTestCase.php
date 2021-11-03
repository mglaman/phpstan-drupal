<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use PHPStan\Testing\RuleTestCase;

/**
 * @template TRule of \PHPStan\Rules\Rule
 */
abstract class DrupalRuleTestCase extends RuleTestCase {

    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/../fixtures/config/phpunit-drupal-phpstan.neon',
        ]);
    }

}
