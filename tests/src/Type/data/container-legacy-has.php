<?php

namespace DrupalContainerStaticLegacy;

use Drupal\service_map\MyService;
use function PHPStan\Testing\assertType;

function test(): void {
    $container = \Drupal::getContainer();

    // Legacy behavior restored via containerHasAlwaysTrue: true.
    assertType('true', $container->has('service_map.my_service'));
    assertType('false', $container->has('unknown_service'));
}
