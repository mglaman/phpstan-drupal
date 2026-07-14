<?php

namespace DrupalClassResolverDisabled;

use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\service_map\MyService;
use function PHPStan\Testing\assertType;

class Foo {}

function test(): void {
    // With classResolverReturnType disabled the extensions fall back to the
    // declared signature types, so instanceof assertions remain meaningful.
    assertType('object', (new ClassResolver())->getInstanceFromDefinition(Foo::class));
    assertType('object', \Drupal::service('class_resolver')->getInstanceFromDefinition(Foo::class));
    assertType('object', \Drupal::classResolver()->getInstanceFromDefinition(Foo::class));
    assertType('object', \Drupal::classResolver(Foo::class));
    assertType('object', (new ClassResolver())->getInstanceFromDefinition('service_map.my_service'));
    assertType('object', \Drupal::classResolver('service_map.my_service'));
    assertType('object', (new ClassResolver())->getInstanceFromDefinition(MyService::class));

    // The zero-argument call still resolves to the class resolver itself.
    assertType(ClassResolverInterface::class, \Drupal::classResolver());
}
