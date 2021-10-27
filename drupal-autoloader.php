<?php declare(strict_types=1);

use mglaman\PHPStanDrupal\Drupal\DrupalAutoloader;
use PHPStan\DependencyInjection\Container;

assert($container instanceof Container);
if ($container === NULL && !($container instanceof Container)) {
    throw new \PHPStan\ShouldNotHappenException('The autoloader did not receive the container.');
}

if (!defined('DRUPAL_TEST_IN_CHILD_SITE')) {
    define('DRUPAL_TEST_IN_CHILD_SITE', false);
}

$drupalAutoloader = new DrupalAutoloader();
$drupalAutoloader->register($container);
