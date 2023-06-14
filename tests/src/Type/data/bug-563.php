<?php

namespace Bug563;

use Drupal\Core\Cache\CacheBackendInterface;
use function PHPStan\Testing\assertType;

function foo(): void {
    $container = \Drupal::getContainer();
    assert($container !== null);
    $cache_bins = ['page', 'dynamic_page_cache', 'render'];
    foreach ($cache_bins as $cache_bin) {
        assertType(CacheBackendInterface::class, $container->get("cache.$cache_bin"));
    }
}
