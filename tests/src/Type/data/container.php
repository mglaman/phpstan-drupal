<?php

namespace DrupalContainerStatic;

use Drupal\service_map\AgainAnotherMyService;
use Drupal\service_map\Concrete;
use Drupal\service_map\MyService;
use Drupal\service_map\Override;
use function PHPStan\Testing\assertType;

$container = \Drupal::getContainer();

assertType(MyService::class, $container->get('service_map.my_service'));
assertType('true', $container->has('service_map.my_service'));
assertType('false', $container->has('unknown_service'));
assertType(MyService::class, $container->get('service_map.concrete_service'));
assertType(MyService::class, $container->get('service_map.concrete_service_with_a_parent_which_has_a_parent'));
assertType(Override::class, $container->get('service_map.concrete_service_overriding_definition_of_its_parent'));
assertType(Concrete::class, $container->get('service_map.concrete_overriding_its_parent_which_has_a_parent'));
