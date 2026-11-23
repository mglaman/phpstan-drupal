<?php

namespace DrupalContainerParameter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use function PHPStan\Testing\assertType;

function test(ContainerInterface $container, string $name, bool $flag): void {
    // Kept as the kind of value, not the value: the kernel sets app.root.
    assertType('string', $container->getParameter('app.root'));
    assertType('int', $container->getParameter('entity.memory_cache.slots'));
    assertType('bool', $container->getParameter('security.enable_super_user'));
    assertType('array', $container->getParameter('renderer.config'));
    assertType('array', $container->getParameter('cache_default_bin_backends'));
    assertType('float', $container->getParameter('service_map.ratio'));
    assertType('string', $container->getParameter('jsonapi.base_path'));

    // A null default leaves the type to the site.
    assertType('array|bool|float|int|string|UnitEnum|null', $container->getParameter('password.algorithm'));
    // Could come from the site's services.yml or a service provider.
    assertType('array|bool|float|int|string|UnitEnum|null', $container->getParameter('unknown.parameter'));
    assertType('array|bool|float|int|string|UnitEnum|null', $container->getParameter($name));

    assertType('int|string', $container->getParameter($flag ? 'app.root' : 'entity.memory_cache.slots'));
    assertType('array|bool|float|int|string|UnitEnum|null', $container->getParameter($flag ? 'app.root' : 'unknown.parameter'));
}

function drupal_container(): void {
    assertType('string', \Drupal::getContainer()->getParameter('site.path'));
}
