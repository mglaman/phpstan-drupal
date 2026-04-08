<?php

namespace DrupalClassResolver;

use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\service_map\MyService;
use function PHPStan\Testing\assertType;

class Foo {}

function test_class_string_vars(): void {
    /** @var class-string<Foo> $fooClass */
    $fooClass = Foo::class;
    assertType(Foo::class, (new ClassResolver())->getInstanceFromDefinition($fooClass));
    assertType(Foo::class, \Drupal::service('class_resolver')->getInstanceFromDefinition($fooClass));
    assertType(Foo::class, \Drupal::classResolver()->getInstanceFromDefinition($fooClass));
    assertType(Foo::class, \Drupal::classResolver($fooClass));

    /** @var class-string<MyService> $serviceClass */
    $serviceClass = MyService::class;
    assertType(MyService::class, (new ClassResolver())->getInstanceFromDefinition($serviceClass));
    assertType(MyService::class, \Drupal::service('class_resolver')->getInstanceFromDefinition($serviceClass));
    assertType(MyService::class, \Drupal::classResolver()->getInstanceFromDefinition($serviceClass));
    assertType(MyService::class, \Drupal::classResolver($serviceClass));
}

function test(): void {
    assertType(Foo::class, (new ClassResolver())->getInstanceFromDefinition(Foo::class));
    assertType(Foo::class, \Drupal::service('class_resolver')->getInstanceFromDefinition(Foo::class));
    assertType(Foo::class, \Drupal::classResolver()->getInstanceFromDefinition(Foo::class));
    assertType(Foo::class, \Drupal::classResolver(Foo::class));
    assertType(MyService::class, (new ClassResolver())->getInstanceFromDefinition('service_map.my_service'));
    assertType(MyService::class, \Drupal::service('class_resolver')->getInstanceFromDefinition('service_map.my_service'));
    assertType(MyService::class, \Drupal::classResolver()->getInstanceFromDefinition('service_map.my_service'));
    assertType(MyService::class, \Drupal::classResolver('service_map.my_service'));

    assertType(MyService::class, (new ClassResolver())->getInstanceFromDefinition(MyService::class));
    assertType(MyService::class, \Drupal::service('class_resolver')->getInstanceFromDefinition(MyService::class));
    assertType(MyService::class, \Drupal::classResolver()->getInstanceFromDefinition(MyService::class));
    assertType(MyService::class, \Drupal::classResolver(MyService::class));
}
