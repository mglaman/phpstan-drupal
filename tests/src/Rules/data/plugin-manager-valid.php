<?php

declare(strict_types = 1);

namespace Drupal\phpstan_fixtures;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Provides the default example manager.
 */
class ExamplePluginManager extends DefaultPluginManager {

    /**
     * The theme handler.
     *
     * @var \Drupal\Core\Extension\ThemeHandlerInterface
     */
    protected $themeHandler;

    /**
     * Provides default values for all style_plugin plugins.
     *
     * @var array
     */
    protected $defaults = [
        // Add required and optional plugin properties.
        'id' => '',
        'enabled' => TRUE,
        'label' => '',
        'description' => '',
        'render' => [],
    ];

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
     *   The module handler.
     * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
     *   The theme handler.
     * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
     *   Cache backend instance to use.
     */
    public function __construct(
        ModuleHandlerInterface $module_handler,
        ThemeHandlerInterface $theme_handler,
        CacheBackendInterface $cache_backend
    ) {
        $this->moduleHandler = $module_handler;
        $this->themeHandler = $theme_handler;
        $this->alterInfo('ui_examples_examples');
        $this->setCacheBackend($cache_backend, 'ui_examples', ['ui_examples']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDiscovery() {
        $this->discovery = new YamlDiscovery('ui_examples', $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories());
        $this->discovery->addTranslatableProperty('label', 'label_context');
        $this->discovery->addTranslatableProperty('description', 'description_context');
        $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
        return $this->discovery;
    }

    /**
     * {@inheritdoc}
     */
    public function processDefinition(&$definition, $plugin_id) : void {
        parent::processDefinition($definition, $plugin_id);
        // @todo Add validation of the plugin definition here.
        if (empty($definition['id'])) {
            throw new PluginException(sprintf('Example plugin property (%s) definition "id" is required.', $plugin_id));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function alterDefinitions(&$definitions) {
        foreach ($definitions as $definition_key => $definition_info) {
            if (isset($definition_info['enabled']) && !$definition_info['enabled']) {
                unset($definitions[$definition_key]);
                continue;
            }
        }

        parent::alterDefinitions($definitions);
    }

    /**
     * {@inheritdoc}
     */
    protected function providerExists($provider) : bool {
        return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
    }

}
