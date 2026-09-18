<?php

namespace DrupalClassResolverDisabledLegacyCore;

use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\service_map\MyService;
use function PHPStan\Testing\assertType;

class Foo {}

function test(): void {
    // On cores without generics on getInstanceFromDefinition() the signature
    // fallback is plain `object` for class-string arguments too. Newer cores
    // declare a conditional return type that narrows these natively, so the
    // test class only gathers this file when the docblock has no @template.
    assertType('object', (new ClassResolver())->getInstanceFromDefinition(Foo::class));
    assertType('object', \Drupal::service('class_resolver')->getInstanceFromDefinition(Foo::class));
    assertType('object', \Drupal::classResolver()->getInstanceFromDefinition(Foo::class));
    assertType('object', \Drupal::classResolver(Foo::class));
    assertType('object', \Drupal::classResolver('service_map.my_service'));
    assertType('object', (new ClassResolver())->getInstanceFromDefinition(MyService::class));
}
