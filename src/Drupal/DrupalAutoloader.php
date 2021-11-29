<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use DrupalFinder\DrupalFinder;
use Nette\Utils\Finder;
use PHPStan\DependencyInjection\Container;
use Symfony\Component\Yaml\Yaml;

class DrupalAutoloader
{

    /**
     * @var \Composer\Autoload\ClassLoader
     */
    private $autoloader;

    /**
     * @var string
     */
    private $drupalRoot;

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
     * @var array<array<string, string>>
     */
    private $serviceMap = [];

    /**
     * @var array<string, string>
     */
    private $serviceYamls = [];

    /**
     * @var array<string, string>
     */
    private $serviceClassProviders = [];

    /**
     * @var array
     */
    private $namespaces = [];

    public function register(Container $container): void
    {
        $drupalParams = $container->getParameter('drupal');
        $drupalRoot = realpath($drupalParams['drupal_root']);
        $finder = new DrupalFinder();
        $finder->locateRoot($drupalRoot);

        $drupalRoot = $finder->getDrupalRoot();
        $drupalVendorRoot = $finder->getVendorDir();
        if (! (bool) $drupalRoot || ! (bool) $drupalVendorRoot) {
            throw new \RuntimeException("Unable to detect Drupal at $drupalRoot");
        }

        $this->drupalRoot = $drupalRoot;

        $this->autoloader = include $drupalVendorRoot . '/autoload.php';

        $this->serviceYamls['core'] = $drupalRoot . '/core/core.services.yml';
        $this->serviceClassProviders['core'] = '\Drupal\Core\CoreServiceProvider';
        $this->serviceMap['service_provider.core.service_provider'] = ['class' => $this->serviceClassProviders['core']];

        $extensionDiscovery = new ExtensionDiscovery($this->drupalRoot);
        $extensionDiscovery->setProfileDirectories([]);
        $profiles = $extensionDiscovery->scan('profile');
        $profile_directories = array_map(static function (Extension $profile) : string {
            return $profile->getPath();
        }, $profiles);
        $extensionDiscovery->setProfileDirectories($profile_directories);

        $this->moduleData = array_merge($extensionDiscovery->scan('module'), $profiles);
        usort($this->moduleData, static function (Extension $a, Extension $b) {
            return strpos($a->getName(), '_test') !== false ? 10 : 0;
        });
        $this->themeData = $extensionDiscovery->scan('theme');
        $this->addCoreTestNamespaces();
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
            $theme_dir = $this->drupalRoot . '/' . $extension->getPath();
            $theme_settings_file = $theme_dir . '/theme-settings.php';
            if (file_exists($theme_settings_file)) {
                $this->loadAndCatchErrors($theme_settings_file);
            }
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

        foreach ($this->serviceYamls as $extension => $serviceYaml) {
            $yaml = Yaml::parseFile($serviceYaml);
            // Weed out service files which only provide parameters.
            if (!isset($yaml['services']) || !is_array($yaml['services'])) {
                continue;
            }
            foreach ($yaml['services'] as $serviceId => $serviceDefinition) {
                // Check if this is an alias shortcut.
                // @link https://symfony.com/doc/4.4/service_container/alias_private.html#aliasing
                if (is_string($serviceDefinition)) {
                    $serviceDefinition = [
                        'alias' => str_replace('@', '', $serviceDefinition),
                    ];
                }
                // Prevent \Nette\DI\ContainerBuilder::completeStatement from array_walk_recursive into the arguments
                // and thinking these are real services for PHPStan's container.
                if (isset($serviceDefinition['arguments']) && is_array($serviceDefinition['arguments'])) {
                    array_walk($serviceDefinition['arguments'], function (&$argument) : void {
                        if (is_array($argument) || !is_string($argument)) {
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
                $this->serviceMap[$serviceId] = $serviceDefinition;
            }
        }

        $service_map = $container->getByType(ServiceMap::class);
        $service_map->setDrupalServices($this->serviceMap);

        if (interface_exists(\PHPUnit\Framework\Test::class)
            && class_exists('Drupal\TestTools\PhpUnitCompatibility\PhpUnit8\ClassWriter')) {
            \Drupal\TestTools\PhpUnitCompatibility\PhpUnit8\ClassWriter::mutateTestBase($this->autoloader);
        }
    }

    protected function loadLegacyIncludes(): void
    {
        /** @var \SplFileInfo $file */
        foreach (Finder::findFiles('*.inc')->in($this->drupalRoot . '/core/includes') as $file) {
            require_once $file->getPathname();
        }
    }

    protected function addCoreTestNamespaces(): void
    {
        // Add core test namespaces.
        $core_tests_dir = $this->drupalRoot . '/core/tests/Drupal';
        $this->namespaces['Drupal\\BuildTests'] = $core_tests_dir . '/BuildTests';
        $this->namespaces['Drupal\\FunctionalJavascriptTests'] = $core_tests_dir . '/FunctionalJavascriptTests';
        $this->namespaces['Drupal\\FunctionalTests'] =  $core_tests_dir . '/FunctionalTests';
        $this->namespaces['Drupal\\KernelTests'] = $core_tests_dir . '/KernelTests';
        $this->namespaces['Drupal\\Tests'] = $core_tests_dir . '/Tests';
        $this->namespaces['Drupal\\TestSite'] = $core_tests_dir . '/TestSite';
        $this->namespaces['Drupal\\TestTools'] = $core_tests_dir . '/TestTools';
        $this->namespaces['Drupal\\Tests\\TestSuites'] = $this->drupalRoot . '/core/tests/TestSuites';
    }

    protected function addModuleNamespaces(): void
    {
        foreach ($this->moduleData as $module) {
            $module_name = $module->getName();
            $module_dir = $this->drupalRoot . '/' . $module->getPath();
            $this->namespaces["Drupal\\$module_name"] = $module_dir . '/src';

            // Extensions can have a \Drupal\Tests\extension namespace for test cases, traits, and other classes such
            // as those that extend \Drupal\TestSite\TestSetupInterface.
            // @see drupal_phpunit_get_extension_namespaces()
            $module_test_dir = $module_dir . '/tests/src';
            if (is_dir($module_test_dir)) {
                $this->namespaces["Drupal\\Tests\\$module_name"] = $module_test_dir;
            }

            $servicesFileName = $module_dir . '/' . $module_name . '.services.yml';
            if (file_exists($servicesFileName)) {
                $this->serviceYamls[$module_name] = $servicesFileName;
            }
            $camelized = $this->camelize($module_name);
            $name = "{$camelized}ServiceProvider";
            $class = "Drupal\\{$module_name}\\{$name}";

            $this->serviceClassProviders[$module_name] = $class;
            $serviceId = "service_provider.$module_name.service_provider";
            $this->serviceMap[$serviceId] = ['class' => $class];

            $this->registerExtensionTestNamespace($module);
        }
    }
    protected function addThemeNamespaces(): void
    {
        foreach ($this->themeData as $theme_name => $theme) {
            $theme_dir = $this->drupalRoot . '/' . $theme->getPath();
            $this->namespaces["Drupal\\$theme_name"] = $theme_dir . '/src';
            $this->registerExtensionTestNamespace($theme);
        }
    }

    protected function registerExtensionTestNamespace(Extension $extension): void
    {
        $suite_names = ['Unit', 'Kernel', 'Functional', 'Build', 'FunctionalJavascript'];
        $dir = $this->drupalRoot . '/' . $extension->getPath();
        $test_dir = $dir . '/tests/src';
        if (is_dir($test_dir)) {
            foreach ($suite_names as $suite_name) {
                $suite_dir = $test_dir . '/' . $suite_name;
                if (is_dir($suite_dir)) {
                    // Register the PSR-4 directory for PHPUnit-based suites.
                    $this->namespaces['Drupal\\Tests\\' . $extension->getName() . '\\' . $suite_name . '\\'][] = $suite_dir;
                }
            }
            // Extensions can have a \Drupal\Tests\extension\Traits namespace for
            // cross-suite trait code.
            $trait_dir = $test_dir . '/Traits';
            if (is_dir($trait_dir)) {
                $this->namespaces['Drupal\\Tests\\' . $extension->getName() . '\\Traits\\'][] = $trait_dir;
            }
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
            // This can happen when drupal_get_path or drupal_get_filename are used outside the scope of a function.
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
