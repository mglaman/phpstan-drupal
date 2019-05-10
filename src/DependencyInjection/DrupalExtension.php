<?php declare(strict_types=1);

namespace PHPStan\DependencyInjection;

use DrupalFinder\DrupalFinder;
use Nette;
use Nette\DI\CompilerExtension;
use Nette\DI\Config\Helpers;
use PHPStan\Drupal\ExtensionDiscovery;
use PHPStan\Rules\Classes\EnhancedRequireParentConstructCallRule;
use PHPStan\Rules\Classes\RequireParentConstructCallRule;
use Symfony\Component\Yaml\Yaml;

class DrupalExtension extends CompilerExtension
{
    /**
     * @var array
     */
    protected $defaultConfig = [
        'modules' => [],
        'themes' => [],
        'drupal_root' => '',
    ];

    /**
     * @var string
     */
    private $drupalRoot;

    /**
     * @var string
     */
    private $drupalVendorDir;

    /**
     * List of available modules.
     *
     * @var \PHPStan\Drupal\Extension[]
     */
    protected $moduleData = [];

    /**
     * List of available themes.
     *
     * @var \PHPStan\Drupal\Extension[]
     */
    protected $themeData = [];

    /**
     * @var array
     */
    private $modules = [];

    /**
     * @var array
     */
    private $themes = [];

    public function loadConfiguration(): void
    {
        /** @var array */
        $config = Nette\Schema\Helpers::merge($this->config, $this->defaultConfig);
        $builder = $this->getContainerBuilder();
        $builder->parameters['autoload_files'][] = dirname(__DIR__, 2) . '/drupal-autoloader.php';
        $builder->parameters['drupal_root'] = $config['drupal_root'];
        $builder->parameters['drupal']['entityTypeStorageMapping'] = $config['entityTypeStorageMapping'] ?? [];
        $builder->parameters['drupalServiceMap'] = [];

        foreach ($builder->getDefinitions() as $definition) {
            if ($definition instanceof Nette\DI\Definitions\FactoryDefinition) {
                $resultDefinition = $definition->getResultDefinition();
                $factory = $resultDefinition->getFactory();
                if ($factory->entity === RequireParentConstructCallRule::class) {
                    $resultDefinition->setFactory(EnhancedRequireParentConstructCallRule::class);
                }
            }
        }
        return;

        $this->modules = $config['modules'] ?? [];
        $this->themes = $config['themes'] ?? [];

        $builder = $this->getContainerBuilder();

        // Build the service definitions...
        $extensionDiscovery = new ExtensionDiscovery($this->drupalRoot);
        $extensionDiscovery->setProfileDirectories([]);
        $profiles = $extensionDiscovery->scan('profile');
        $profile_directories = array_map(static function (\PHPStan\Drupal\Extension $profile) : string {
            return $profile->getPath();
        }, $profiles);
        $extensionDiscovery->setProfileDirectories($profile_directories);


        $serviceYamls = [
            'core' => $this->drupalRoot . '/core/core.services.yml',
        ];
        $serviceClassProviders = [
            'core' => 'Drupal\Core\CoreServiceProvider',
        ];
        $extensions = array_merge($extensionDiscovery->scan('module'), $profiles);
        foreach ($extensions as $extension) {
            $module_dir = $this->drupalRoot . '/' . $extension->getPath();
            $moduleName = $extension->getName();
            $servicesFileName = $module_dir . '/' . $moduleName . '.services.yml';
            if (file_exists($servicesFileName)) {
                $serviceYamls[$moduleName] = $servicesFileName;
            }

            $camelized = $this->camelize($extension->getName());
            $name = "{$camelized}ServiceProvider";
            $class = "Drupal\\{$moduleName}\\{$name}";

            if (class_exists($class)) {
                $serviceClassProviders[$moduleName] = $class;
            }
        }

        foreach ($serviceYamls as $extension => $serviceYaml) {
            $yaml = Yaml::parseFile($serviceYaml);
            // Weed out service files which only provide parameters.
            if (!isset($yaml['services']) || !is_array($yaml['services'])) {
                continue;
            }
            foreach ($yaml['services'] as $serviceId => $serviceDefinition) {
                // Prevent \Nette\DI\ContainerBuilder::completeStatement from array_walk_recursive into the arguments
                // and thinking these are real services for PHPStan's container.
                if (isset($serviceDefinition['arguments']) && is_array($serviceDefinition['arguments'])) {
                    array_walk($serviceDefinition['arguments'], function (&$argument) : void {
                        if (is_array($argument)) {
                            // @todo fix for @http_kernel.controller.argument_metadata_factory
                            $argument = '';
                        } else {
                            $argument = str_replace('@', '', $argument);
                        }
                    });
                }
                // @todo sanitize "calls" and "configurator" and "factory"
                /**
                jsonapi.params.enhancer:
                    class: Drupal\jsonapi\Routing\JsonApiParamEnhancer
                    calls:
                        - [setContainer, ['@service_container']]
                    tags:
                        - { name: route_enhancer }
                 */
                unset($serviceDefinition['tags'], $serviceDefinition['calls'], $serviceDefinition['configurator'], $serviceDefinition['factory']);
                $builder->parameters['drupalServiceMap'][$serviceId] = $serviceDefinition;
            }
        }
    }
}
