<?php declare(strict_types=1);

namespace PHPStan\Drupal;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use DrupalFinder\DrupalFinder;
use Nette\Utils\Finder;
use Symfony\Component\Yaml\Yaml;

class Bootstrap
{
    /**
     * @var array
     */
    protected $defaultConfig = [
        'modules' => [],
        'themes' => [],
    ];

    /**
     * @var \Composer\Autoload\ClassLoader
     */
    private $autoloader;

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
     * @var Extension[]
     */
    protected $moduleData = [];

    /**
     * List of available themes.
     *
     * @var Extension[]
     */
    protected $themeData = [];

    /**
     * @var ?\PHPStan\Drupal\ExtensionDiscovery
     */
    private $extensionDiscovery;

    /**
     * @var array
     */
    private $namespaces = [];

    /**
     * @var \PHPStan\DependencyInjection\Container
     */
    private $container;

    public function __construct(\PHPStan\DependencyInjection\Container $container)
    {
        $this->container = $container;
    }

    private function registerServiceMap()
    {
        $serviceYamls = [
            'core' => $this->drupalRoot . '/core/core.services.yml',
        ];
        $serviceClassProviders = [
            'core' => 'Drupal\Core\CoreServiceProvider',
        ];
        foreach ($this->moduleData as $extension) {
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
                //$builder->parameters['drupalServiceMap'][$serviceId] = $serviceDefinition;
            }
        }
    }

    public function register(): void
    {
        $this->registerDrupalPath();
        $this->autoloader = include $this->drupalVendorDir . '/autoload.php';
        $this->registerExtensionData();

        $this->addCoreNamespaces();
        $this->addModuleNamespaces();
        $this->addThemeNamespaces();
        $this->registerPs4Namespaces($this->namespaces);
        $this->loadLegacyIncludes();

        foreach ($this->moduleData as $extension) {
            $this->loadExtension($extension);

            $module_name = $extension->getName();
            $module_dir = $this->drupalRoot . '/' . $extension->getPath();
            // Add .install
            if (file_exists($module_dir . '/' . $module_name . '.install')) {
                $ignored_install_files = ['entity_test', 'entity_test_update', 'update_test_schema'];
                if (!in_array($module_name, $ignored_install_files, true)) {
                    $this->loadAndCatchErrors($module_dir . '/' . $module_name . '.install');
                }
            }
            // Add .post_update.php
            if (file_exists($module_dir . '/' . $module_name . '.post_update.php')) {
                $this->loadAndCatchErrors($module_dir . '/' . $module_name . '.post_update.php');
            }
            // Add misc .inc that are magically allowed via hook_hook_info.
            $magic_hook_info_includes = [
                'views',
                'views_execution',
                'tokens',
                'search_api',
                'pathauto',
            ];
            foreach ($magic_hook_info_includes as $hook_info_include) {
                if (file_exists($module_dir . "/$module_name.$hook_info_include.inc")) {
                    $this->loadAndCatchErrors($module_dir . "/$module_name.$hook_info_include.inc");
                }
            }
        }
        foreach ($this->themeData as $extension) {
            $this->loadExtension($extension);
        }

        if (class_exists(\Drush\Drush::class)) {
            $reflect = new \ReflectionClass(\Drush\Drush::class);
            if ($reflect->getFileName() !== false) {
                $levels = 2;
                if (\Drush\Drush::getMajorVersion() < 9) {
                    $levels = 3;
                }
                $drushDir = dirname($reflect->getFileName(), $levels);
                /** @var \SplFileInfo $file */
                foreach (Finder::findFiles('*.inc')->in($drushDir . '/includes') as $file) {
                    require_once $file->getPathname();
                }
            }
        }
    }

    private function registerDrupalPath(): void
    {
        $drupalRoot = $this->container->getParameter('drupal_root');
        if ($drupalRoot !== '' && realpath($drupalRoot) !== false && is_dir($drupalRoot)) {
            $start_path = realpath($drupalRoot);
        } else {
            $start_path = $this->container->getParameter('currentWorkingDirectory');
        }
        if ($start_path === false) {
            throw new \RuntimeException('Cannot determine the Drupal root from ' . $start_path);
        }
        $finder = new DrupalFinder();
        $finder->locateRoot($start_path);
        $this->drupalRoot = $finder->getDrupalRoot();
        $this->drupalVendorDir = $finder->getVendorDir();
        if (! (bool) $this->drupalRoot || ! (bool) $this->drupalVendorDir) {
            throw new \RuntimeException("Unable to detect Drupal at $start_path");
        }

        // DRUPAL_TEST_IN_CHILD_SITE is only defined in the \Drupal\Core\DrupalKernel::bootEnvironment method when
        // Drupal is bootstrapped. Since we don't actually invoke the bootstrapping of Drupal, define the constant here
        // as `false`. And we have to conditionally define it due to our own PHPUnit tests
        if (!defined('DRUPAL_TEST_IN_CHILD_SITE')) {
            define('DRUPAL_TEST_IN_CHILD_SITE', false);
        }
    }
    private function registerExtensionData(): void
    {
        $this->extensionDiscovery = new ExtensionDiscovery($this->drupalRoot);
        $this->extensionDiscovery->setProfileDirectories([]);
        $profiles = $this->extensionDiscovery->scan('profile');
        $profile_directories = array_map(static function (Extension $profile) : string {
            return $profile->getPath();
        }, $profiles);
        $this->extensionDiscovery->setProfileDirectories($profile_directories);

        $this->moduleData = array_merge($this->extensionDiscovery->scan('module'), $profiles);
        usort($this->moduleData, static function (Extension $a, Extension $b) {
            // blazy_test causes errors, ensure it is loaded last.
            return $a->getName() === 'blazy_test' ? 10 : 0;
        });
        $this->themeData = $this->extensionDiscovery->scan('theme');
    }

    protected function loadLegacyIncludes(): void
    {
        /** @var \SplFileInfo $file */
        foreach (Finder::findFiles('*.inc')->in($this->drupalRoot . '/core/includes') as $file) {
            require_once $file->getPathname();
        }
    }

    protected function addCoreNamespaces(): void
    {
        foreach (['Core', 'Component'] as $parent_directory) {
            $path = $this->drupalRoot . '/core/lib/Drupal/' . $parent_directory;
            $parent_namespace = 'Drupal\\' . $parent_directory;
            foreach (new \DirectoryIterator($path) as $component) {
                if (!$component->isDot() && $component->isDir()) {
                    $this->namespaces[$parent_namespace . '\\' . $component->getFilename()] = $path . '/' . $component->getFilename();
                }
            }
        }

        // Add core test namespaces.
        $core_tests_dir = $this->drupalRoot . '/core/tests/Drupal';
        $this->namespaces['Drupal\\Tests'] = $core_tests_dir . '/Tests';
        $this->namespaces['Drupal\\TestSite'] = $core_tests_dir . '/TestSite';
        $this->namespaces['Drupal\\KernelTests'] = $core_tests_dir . '/KernelTests';
        $this->namespaces['Drupal\\FunctionalTests'] =  $core_tests_dir . '/FunctionalTests';
        $this->namespaces['Drupal\\FunctionalJavascriptTests'] = $core_tests_dir . '/FunctionalJavascriptTests';
        $this->namespaces['Drupal\\Tests\\TestSuites'] = $this->drupalRoot . '/core/tests/TestSuites';
        $this->namespaces['Drupal\\BuildTests'] = $core_tests_dir . '/BuildTests';
        $this->namespaces['Drupal\\TestTools'] = $core_tests_dir . '/TestTools';
    }

    protected function addModuleNamespaces(): void
    {
        foreach ($this->moduleData as $module) {
            $module_name = $module->getName();
            $module_dir = $this->drupalRoot . '/' . $module->getPath();
            $this->namespaces["Drupal\\$module_name"] = $module_dir . '/src';

            // @see drupal_phpunit_get_extension_namespaces
            $module_test_dir = $module_dir . '/tests/src';
            if (is_dir($module_test_dir)) {
                $suite_names = ['Unit', 'Kernel', 'Functional', 'FunctionalJavascript'];
                foreach ($suite_names as $suite_name) {
                    $suite_dir = $module_test_dir . '/' . $suite_name;
                    if (is_dir($suite_dir)) {
                        // Register the PSR-4 directory for PHPUnit-based suites.
                        $this->namespaces["Drupal\\Tests\\$module_name\\$suite_name"] = $suite_dir;
                    }

                    // Extensions can have a \Drupal\extension\Traits namespace for
                    // cross-suite trait code.
                    $trait_dir = $module_test_dir . '/Traits';
                    if (is_dir($trait_dir)) {
                        $this->namespaces["Drupal\\Tests\\$module_name\\Traits"] = $trait_dir;
                    }
                }
            }
        }
    }
    protected function addThemeNamespaces(): void
    {
        foreach ($this->themeData as $theme_name => $theme) {
            $theme_dir = $this->drupalRoot . '/' . $theme->getPath();
            $this->namespaces["Drupal\\$theme_name"] = $theme_dir . '/src';
        }
    }

    protected function registerPs4Namespaces(array $namespaces): void
    {
        foreach ($namespaces as $prefix => $paths) {
            if (is_array($paths)) {
                foreach ($paths as $key => $value) {
                    $paths[$key] = $value;
                }
            }
            $this->autoloader->addPsr4($prefix . '\\', $paths);
        }
    }
    protected function loadExtension(Extension $extension): void
    {
        try {
            $extension->load();
        } catch (\Throwable $e) {
            // Something prevented the extension file from loading.
            // This can happen when drupal_get_path or drupal_get_filename are used outside of the scope of a function.
        }
    }

    protected function loadAndCatchErrors(string $path): void
    {
        try {
            require_once $path;
        } catch (ContainerNotInitializedException $e) {
            $path = str_replace(dirname($this->drupalRoot) . '/', '', $path);
            // This can happen when drupal_get_path or drupal_get_filename are used outside of the scope of a function.
            @trigger_error("$path invoked the Drupal container outside of the scope of a function or class method. It was not loaded.", E_USER_WARNING);
        } catch (\Throwable $e) {
            $path = str_replace(dirname($this->drupalRoot) . '/', '', $path);
            // Something prevented the extension file from loading.
            @trigger_error("$path failed loading due to {$e->getMessage()}", E_USER_WARNING);
        }
    }

    protected function camelize(string $id): string
    {
        return strtr(ucwords(strtr($id, ['_' => ' ', '.' => '_ ', '\\' => '_ '])), [' ' => '']);
    }
}
