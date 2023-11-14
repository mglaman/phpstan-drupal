<?php

namespace DrupalServiceStatic;

use Drupal\service_map\MyService;
use function PHPStan\Testing\assertType;

function test(): void {
    assertType(MyService::class, \Drupal::service('service_map.my_service'));
    assertType(MyService::class, \Drupal::service(MyService::class));
}
