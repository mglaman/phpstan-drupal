<?php

use PHPStan\DependencyInjection\Container;
use PHPStan\Drupal\Bootstrap;

assert($container instanceof Container);
$drupalAutoloader = new Bootstrap($container);
$drupalAutoloader->register();
