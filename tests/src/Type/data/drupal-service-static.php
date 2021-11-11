<?php

namespace DrupalServiceStatic;

use Drupal\service_map\MyService;
use function PHPStan\Testing\assertType;

assertType(MyService::class, \Drupal::service('service_map.my_service'));
