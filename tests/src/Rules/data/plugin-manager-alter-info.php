<?php

namespace PluginManagerAlterInfo;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class AnnotationsMissingAlterInfo extends DefaultPluginManager {

    public function __construct(
        \Traversable $namespaces,
        ModuleHandlerInterface $module_handler,
        CacheBackendInterface $cache_backend,
        string $type,
    ) {
        parent::__construct(
            'Plugin/Bar',
            $namespaces,
            $module_handler,
            'BarInterface',
            'BarAnnotation',
        );
    }
}
class AnnotationsWithAlterInfo extends DefaultPluginManager {

    public function __construct(
        \Traversable $namespaces,
        ModuleHandlerInterface $module_handler,
    ) {
        parent::__construct(
            'Plugin/Bar',
            $namespaces,
            $module_handler,
            'BarInterface',
            'BarAnnotation',
        );
        $this->alterInfo('bar');
    }
}
