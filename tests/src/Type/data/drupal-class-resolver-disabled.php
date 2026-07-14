<?php

namespace DrupalClassResolverDisabled;

use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use function PHPStan\Testing\assertType;

function test(): void {
    // With classResolverReturnType disabled the extensions fall back to the
    // declared signature types. Service IDs are not class-strings, so these
    // stay `object` even on cores whose docblocks declare generics.
    assertType('object', (new ClassResolver())->getInstanceFromDefinition('service_map.my_service'));
    assertType('object', \Drupal::service('class_resolver')->getInstanceFromDefinition('service_map.my_service'));
    assertType('object', \Drupal::classResolver()->getInstanceFromDefinition('service_map.my_service'));

    // The zero-argument call still resolves to the class resolver itself.
    assertType(ClassResolverInterface::class, \Drupal::classResolver());
}
