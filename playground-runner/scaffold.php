<?php

/**
 * @file
 *
 * Hacks needed to get phpstan-drupal to work using DrupalFinder when the
 * drupal/core package is in the vendor directory.
 */

file_put_contents(__DIR__ . '/vendor/drupal/autoload.php', <<<AUTOLOAD
<?php

/**
 * @file
 * Includes the autoloader created by Composer.
 *
 * This file was generated by drupal-scaffold.
 *
 * @see composer.json
 * @see index.php
 * @see core/install.php
 * @see core/rebuild.php
 * @see core/modules/statistics/statistics.php
 */

return require __DIR__ . '/../autoload.php';

AUTOLOAD);
file_put_contents(__DIR__ . '/vendor/drupal/composer.json', <<<COMPOSER
{
    "config": {
        "vendor-dir": "../"
    }
}
COMPOSER
);