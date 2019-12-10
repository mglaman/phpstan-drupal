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

/**
 * @deprecated
 *
 * This extension is currently unused due to changes in phpstan:^0.12.
 *
 * It was used to dynamically provide information about Drupal services for its rules. Workarounds need to be found.
 */
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

    public function loadConfiguration(): void
    {
        /** @var array */
        $config = Nette\Schema\Helpers::merge($this->config, $this->defaultConfig);

        $finder = new DrupalFinder();

        if ($config['drupal_root'] !== '' && realpath($config['drupal_root']) !== false && is_dir($config['drupal_root'])) {
            $start_path = $config['drupal_root'];
        } else {
            $start_path = dirname($GLOBALS['autoloaderInWorkingDirectory']);
        }

        $finder->locateRoot($start_path);
        $drupalRoot = $finder->getDrupalRoot();
        $drupalVendorDir = $finder->getVendorDir();
        if (! (bool) $drupalRoot || ! (bool) $drupalVendorDir) {
            throw new \RuntimeException("Unable to detect Drupal at $start_path");
        }

        $builder = $this->getContainerBuilder();
        $builder->parameters['bootstrap'] = dirname(__DIR__, 2) . '/phpstan-bootstrap.php';
        $builder->parameters['drupalRoot'] = $drupalRoot;

        $builder->parameters['drupal']['entityTypeStorageMapping'] = $config['entityTypeStorageMapping'] ?? [];

        $builder = $this->getContainerBuilder();
        foreach ($builder->getDefinitions() as $definition) {
            if ($definition instanceof Nette\DI\Definitions\FactoryDefinition) {
                $resultDefinition = $definition->getResultDefinition();
                $factory = $resultDefinition->getFactory();
                if ($factory->entity === RequireParentConstructCallRule::class) {
                    $resultDefinition->setFactory(EnhancedRequireParentConstructCallRule::class);
                }
            }
        }

        // Build the service definitions...
        $extensionDiscovery = new ExtensionDiscovery($drupalRoot);
        $extensionDiscovery->setProfileDirectories([]);
        $profiles = $extensionDiscovery->scan('profile');
        $profile_directories = array_map(static function (\PHPStan\Drupal\Extension $profile) : string {
            return $profile->getPath();
        }, $profiles);
        $extensionDiscovery->setProfileDirectories($profile_directories);


        $serviceYamls = [
            'core' => $drupalRoot . '/core/core.services.yml',
        ];
        $serviceClassProviders = [
            'core' => 'Drupal\Core\CoreServiceProvider',
        ];
        $extensions = array_merge($extensionDiscovery->scan('module'), $profiles);
        foreach ($extensions as $extension) {
            $module_dir = $drupalRoot . '/' . $extension->getPath();
            $moduleName = $extension->getName();
            $servicesFileName = $module_dir . '/' . $moduleName . '.services.yml';
            if (file_exists($servicesFileName)) {
                $serviceYamls[$moduleName] = $servicesFileName;
            }

            $camelized = $this->camelize($extension->getName());
            $name = "{$camelized}ServiceProvider";
            $class = "Drupal\\{$moduleName}\\{$name}";

            $serviceClassProviders[$moduleName] = $class;
            $serviceId = "service_provider.$moduleName.service_provider";
            $builder->parameters['drupal']['serviceMap'][$serviceId] = [
                'class' => $class,
            ];
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
                unset($serviceDefinition['tags']);
                // @todo sanitize "calls" and "configurator" and "factory"
                /**
                jsonapi.params.enhancer:
                    class: Drupal\jsonapi\Routing\JsonApiParamEnhancer
                    calls:
                        - [setContainer, ['@service_container']]
                    tags:
                        - { name: route_enhancer }
                 */
                unset($serviceDefinition['calls']);
                unset($serviceDefinition['configurator']);
                unset($serviceDefinition['factory']);
                $builder->parameters['drupal']['serviceMap'][$serviceId] = $serviceDefinition;
            }
        }
    }

    protected function camelize(string $id): string
    {
        return strtr(ucwords(strtr($id, ['_' => ' ', '.' => '_ ', '\\' => '_ '])), [' ' => '']);
    }
}
