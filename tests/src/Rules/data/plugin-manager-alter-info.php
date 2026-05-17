<?php

namespace PluginManagerAlterInfo;

use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class AnnotationsMissingAlterInfo extends DefaultPluginManager {

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

class YamlWithoutAlterInfo extends DefaultPluginManager {

    public function __construct(
    ) {
    }

    protected function getDiscovery()
    {
        if (!$this->discovery) {
            $this->discovery = new YamlDiscovery('foo', []);
        }
    }

}

class YamlWithAlterInfo extends DefaultPluginManager {

    public function __construct(
    ) {
        $this->alterInfo('baz');
    }

    protected function getDiscovery()
    {
        if (!$this->discovery) {
            $this->discovery = new YamlDiscovery('foo', []);
        }
    }

}
