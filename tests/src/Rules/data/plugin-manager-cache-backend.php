<?php

namespace PluginManagerCacheBackend;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class Foo extends DefaultPluginManager
{

    public function __construct(
        \Traversable $namespaces,
        ModuleHandlerInterface $module_handler,
    ) {
        parent::__construct(
            'Plugin/Foo',
            $namespaces,
            $module_handler,
            'FooInterface',
            'FooAnnotation',
        );
    }

}

class Bar extends DefaultPluginManager
{

    public function __construct(
        \Traversable $namespaces,
        ModuleHandlerInterface $module_handler,
        CacheBackendInterface $cache_backend
    ) {
        parent::__construct(
            'Plugin/Bar',
            $namespaces,
            $module_handler,
            'BarInterface',
            'BarAnnotation',
        );
        $this->setCacheBackend($cache_backend, 'bar_plugins');
    }

}
