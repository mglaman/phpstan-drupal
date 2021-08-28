<?php declare(strict_types=1);

use PHPStan\DependencyInjection\Container;
use PHPStan\Drupal\DrupalAutoloader;

if (!isset($container) || !($container instanceof Container)) {
    throw new \PHPStan\ShouldNotHappenException('The autoloader did not receive the container.');
}

if (!defined('DRUPAL_TEST_IN_CHILD_SITE')) {
    define('DRUPAL_TEST_IN_CHILD_SITE', false);
}

$drupalParams = $container->getParameter('drupal');
$drupalRoot = $drupalParams['drupal_root'];
$drupalAutoloader = new DrupalAutoloader($drupalRoot);
$drupalAutoloader->register($container);
