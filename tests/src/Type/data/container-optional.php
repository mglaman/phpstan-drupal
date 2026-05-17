<?php

namespace DrupalContainerOptional;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\service_map\MyService;
use function PHPStan\Testing\assertType;

function test(): void {
    $container = \Drupal::getContainer();

    assertType(
        'object|null',
        $container->get('unknown_service', ContainerInterface::NULL_ON_INVALID_REFERENCE)
    );

    assertType(
        MyService::class . '|null',
        $container->get('service_map.my_service', ContainerInterface::NULL_ON_INVALID_REFERENCE)
    );

    assertType(
        MyService::class,
        $container->get('service_map.my_service', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE)
    );

    assertType(
        MyService::class,
        $container->get('service_map.my_service', ContainerInterface::RUNTIME_EXCEPTION_ON_INVALID_REFERENCE)
    );

    assertType(
        MyService::class,
        $container->get('service_map.my_service', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
    );

    assertType(
        MyService::class,
        $container->get('service_map.my_service', ContainerInterface::IGNORE_ON_UNINITIALIZED_REFERENCE)
    );
}
