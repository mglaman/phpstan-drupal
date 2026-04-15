<?php

namespace Bug828;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\RenderElementBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

// A render element using ContainerFactoryPluginInterface for DI.
// Should NOT trigger the rule since it uses proper DI via create().
class RenderElementWithDi extends RenderElementBase implements ContainerFactoryPluginInterface {

    public function __construct(array $configuration, $plugin_id, $plugin_definition) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static($configuration, $plugin_id, $plugin_definition);
    }

    public function getInfo() {
        return [];
    }

}

// A render element using \Drupal directly. Should trigger the rule.
class RenderElementWithDrupalCall extends RenderElementBase {

    public function getInfo() {
        return [];
    }

    public function someMethod() {
        \Drupal::service('some.service');
    }

}
