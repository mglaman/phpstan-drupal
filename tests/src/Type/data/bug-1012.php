<?php

namespace Bug1012;

use Symfony\Component\DependencyInjection\ContainerInterface;
use function PHPStan\Testing\assertType;

function foo(string $service_id): void {
    $container = \Drupal::getContainer();
    assert($container !== null);

    assertType('object', $container->get($service_id));
    assertType('object|null', $container->get($service_id, ContainerInterface::NULL_ON_INVALID_REFERENCE));
    assertType('bool', $container->has($service_id));
}
