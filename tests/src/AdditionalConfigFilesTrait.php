<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

trait AdditionalConfigFilesTrait
{
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/../fixtures/config/phpunit-drupal-phpstan.neon',
        ]);
    }
}
