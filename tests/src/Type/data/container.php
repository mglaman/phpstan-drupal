<?php

namespace DrupalContainerStatic;

use Drupal\service_map\MyService;
use function PHPStan\Testing\assertType;

$container = \Drupal::getContainer();

assertType(MyService::class, $container->get('service_map.my_service'));
assertType('true', $container->has('service_map.my_service'));
assertType('false', $container->has('unknown_service'));
