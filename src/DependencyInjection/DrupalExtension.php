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

        $finder = new DrupalFinder();

        if ($config['drupal_root'] !== '' && realpath($config['drupal_root']) !== false && is_dir($config['drupal_root'])) {
            $start_path = $config['drupal_root'];
        } else {
            $start_path = dirname($GLOBALS['autoloaderInWorkingDirectory']);
        }

        $finder->locateRoot($start_path);
        $this->drupalRoot = $finder->getDrupalRoot();
        $this->drupalVendorDir = $finder->getVendorDir();
        if (! (bool) $this->drupalRoot || ! (bool) $this->drupalVendorDir) {
            throw new \RuntimeException("Unable to detect Drupal at $start_path");
        }

        $builder = $this->getContainerBuilder();
        $builder->parameters['bootstrap'] = dirname(__DIR__, 2) . '/phpstan-bootstrap.php';
        $builder->parameters['drupalRoot'] = $this->drupalRoot;

        $this->modules = $config['modules'] ?? [];
        $this->themes = $config['themes'] ?? [];

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
                $builder->parameters['drupalServiceMap'][$serviceId] = $serviceDefinition;
            }
        }
    }

    protected function camelize(string $id): string
    {
        return strtr(ucwords(strtr($id, ['_' => ' ', '.' => '_ ', '\\' => '_ '])), [' ' => '']);
    }

    public function afterCompile(Nette\PhpGenerator\ClassType $class)
    {
        // @todo find a non-hack way to pass the Drupal roots to the bootstrap file.
        $class->getMethod('initialize')->addBody('$GLOBALS["drupalRoot"] = ?;', [$this->drupalRoot]);
        $class->getMethod('initialize')->addBody('$GLOBALS["drupalVendorDir"] = ?;', [$this->drupalVendorDir]);

        // DRUPAL_TEST_IN_CHILD_SITE is only defined in the \Drupal\Core\DrupalKernel::bootEnvironment method when
        // Drupal is bootstrapped. Since we don't actually invoke the bootstrapping of Drupal, define the constant here
        // as `false`. And we have to conditionally define it due to our own PHPUnit tests
        $class->getMethod('initialize')->addBody('
if (!defined("DRUPAL_TEST_IN_CHILD_SITE")) {
  define("DRUPAL_TEST_IN_CHILD_SITE", ?);
}', [false]);
    }
}
