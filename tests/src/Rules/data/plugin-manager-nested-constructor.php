<?php

namespace PluginManagerNestedConstructor;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * A YAML plugin manager with no constructor anywhere in its hierarchy.
 *
 * The anonymous class inside getDiscovery() declares a constructor. The rule
 * must not mistake it for the plugin manager's own constructor.
 */
class NoConstructorWithNestedAnonymousClass extends PluginManagerBase {

    protected function getDiscovery() {
        $this->discovery = new YamlDiscovery('foo', []);
        return $this->discovery;
    }

    public function helper(): object {
        return new class {
            public function __construct() {}
        };
    }
}
