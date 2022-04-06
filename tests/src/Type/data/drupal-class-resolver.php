<?php

namespace DrupalClassResolver;

use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\service_map\MyService;
use function PHPStan\Testing\assertType;

class Foo {}

function test(): void {
    assertType(Foo::class, (new ClassResolver())->getInstanceFromDefinition(Foo::class));
    assertType(Foo::class, \Drupal::service('class_resolver')->getInstanceFromDefinition(Foo::class));
    assertType(Foo::class, \Drupal::classResolver()->getInstanceFromDefinition(Foo::class));
    assertType(Foo::class, \Drupal::classResolver(Foo::class));
    assertType(MyService::class, (new ClassResolver())->getInstanceFromDefinition('service_map.my_service'));
    assertType(MyService::class, \Drupal::service('class_resolver')->getInstanceFromDefinition('service_map.my_service'));
    assertType(MyService::class, \Drupal::classResolver()->getInstanceFromDefinition('service_map.my_service'));
    assertType(MyService::class, \Drupal::classResolver('service_map.my_service'));
}
