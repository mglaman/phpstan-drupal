<?php declare(strict_types=1);

namespace PHPStan\Drupal;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use DrupalFinder\DrupalFinder;
use PHPStan\DependencyInjection\Container;
use Symfony\Component\Yaml\Yaml;

class DrupalAutoloader
{

    /**
     * Taken from drupal_phpunit_get_extension_namespaces.
     */
    private const PHPUNIT_TEST_SUITES = ['Unit', 'Kernel', 'Functional', 'Build', 'FunctionalJavascript'];

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
     * @var \PHPStan\Drupal\ExtensionDiscovery
     */
    private $extensionDiscovery;

    /**
     * @var array
     */
    private $namespaces = [];

    public function __construct(string $root)
    {
        if (!is_readable($root)) {
            throw new \InvalidArgumentException("Unable to read $root");
        }
        $finder = new DrupalFinder();
        $finder->locateRoot($root);
        $drupalRoot = $finder->getDrupalRoot();
        $drupalVendorRoot = $finder->getVendorDir();
        if (! (bool) $drupalRoot || ! (bool) $drupalVendorRoot) {
            throw new \InvalidArgumentException("Unable to detect Drupal at $root");
        }
        $this->drupalRoot = $drupalRoot;
        $this->autoloader = include $drupalVendorRoot . '/autoload.php';
        $this->extensionDiscovery = new ExtensionDiscovery($this->drupalRoot);
    }

    public function register(Container $container): void
    {
        $this->serviceYamls['core'] = $this->drupalRoot . '/core/core.services.yml';
        $this->serviceClassProviders['core'] = '\Drupal\Core\CoreServiceProvider';
        $this->serviceMap['service_provider.core.service_provider'] = ['class' => $this->serviceClassProviders['core']];

        $this->extensionDiscovery->setProfileDirectories([]);
        $profiles = $this->extensionDiscovery->scan('profile');
        $profile_directories = array_map(static function (Extension $profile) : string {
            return $profile->getPath();
        }, $profiles);
        $this->extensionDiscovery->setProfileDirectories($profile_directories);

        $this->loadLegacyIncludes();

        $this->registerExtensions('module');
        $this->registerExtensions('theme');
        $this->registerExtensions('profile');
        $this->registerExtensions('theme_engine');

        $this->registerTestNamespaces();
        $this->loadDrushIncludes();

        // @todo find a way to not need this, but have stubs/drupal_phpunit.stub work.
        if (interface_exists(\PHPUnit\Framework\Test::class)) {
            require_once $this->drupalRoot . '/core/tests/bootstrap.php';
        }

        foreach ($this->serviceYamls as $serviceYaml) {
            $yaml = Yaml::parseFile($serviceYaml);
            // Weed out service files which only provide parameters.
            if (!isset($yaml['services']) || !is_array($yaml['services'])) {
                continue;
            }
            foreach ($yaml['services'] as $serviceId => $serviceDefinition) {
                // Prevent \Nette\DI\ContainerBuilder::completeStatement from array_walk_recursive into the arguments
                // and thinking these are real services for PHPStan's container.
                if (isset($serviceDefinition['arguments']) && is_array($serviceDefinition['arguments'])) {
                    array_walk($serviceDefinition['arguments'], static function (&$argument) : void {
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
        assert($service_map instanceof ServiceMap);
        // @todo this is a hack that needs investigation.
        // We cannot manipulate the service container and add parameters, so we take the existing
        // service and modify it's properties so that its reference is updated within the container.
        //
        // During debug this works, but other times it fails.
        $service_map->setDrupalServices($this->serviceMap);
        // So, to work around whatever is happening we force it into globals.
        $GLOBALS['drupalServiceMap'] = $service_map->getServices();
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

    private function loadLegacyIncludes(): void
    {
        $this->loadIncludesByDirectory('drupal/core', $this->drupalRoot . '/core/includes');
    }

    private function registerExtensions(string $type): void
    {
        if ($type !== 'module' && $type !== 'theme' && $type !== 'profile' && $type !== 'theme_engine') {
            throw new \InvalidArgumentException("Must be 'module', 'theme', 'profile', or 'theme_engine' but got $type");
        }
        // Tracks implementations of hook_hook_info for loading of those files.
        $hook_info_implementations = [];

        $extensions = $this->extensionDiscovery->scan($type);
        usort($extensions, static function (Extension $a, Extension $b) {
            return strpos($a->getName(), '_test') !== false ? 1 : 0;
        });
        foreach ($extensions as $extension) {
            $extension_name = $extension->getName();
            $extension_path = $this->drupalRoot . '/' . $extension->getPath();
            $this->autoloader->addPsr4("Drupal\\{$extension_name}\\", $extension_path . '/src');

            // @see drupal_phpunit_get_extension_namespaces().
            $test_dir = $extension_path . '/tests/src';
            foreach (self::PHPUNIT_TEST_SUITES as $suite_name) {
                $suite_dir = $test_dir . '/' . $suite_name;
                if (is_dir($suite_dir)) {
                    $this->autoloader->addPsr4("Drupal\\Tests\\$extension_name\\$suite_name\\", $suite_dir);
                }
            }
            // Extensions can have a \Drupal\extension\Traits namespace for
            // cross-suite trait code.
            $trait_dir = $test_dir . '/Traits';
            if (is_dir($trait_dir)) {
                $this->autoloader->addPsr4('Drupal\\Tests\\' . $extension_name . '\\Traits\\', $trait_dir);
            }

            $this->loadExtension($extension);

            // Load schema and post_update files.
            if (file_exists($extension_path . '/' . $extension_name . '.install')) {
                // These are ignored as they cause crashes.
                $ignored_install_files = ['entity_test', 'entity_test_update', 'update_test_schema'];
                if (!in_array($extension_name, $ignored_install_files, true)) {
                    $this->loadAndCatchErrors($extension_path . '/' . $extension_name . '.install');
                }
            }
            if (file_exists($extension_path . '/' . $extension_name . '.post_update.php')) {
                $this->loadAndCatchErrors($extension_path . '/' . $extension_name . '.post_update.php');
            }

            if ($type === 'module' || $type === 'profile') {
                // Mimics the buildHookInfo method in the module handler.
                // @see \Drupal\Core\Extension\ModuleHandler::buildHookInfo
                $hook_info_function = $extension_name . '_hook_info';
                if (function_exists($hook_info_function) && is_callable($hook_info_function)) {
                    $result = $hook_info_function();
                    if (is_array($result)) {
                        $groups = array_unique(array_values(array_map(static function (array $hook_info) {
                            return $hook_info['group'];
                        }, $result)));
                        // We do not need the full array structure, we only care
                        // about the group name for loading files.
                        $hook_info_implementations[] = $groups;
                    }
                }

                $servicesFileName = $extension_path . '/' . $extension_name . '.services.yml';
                if (file_exists($servicesFileName)) {
                    $this->serviceYamls[$extension_name] = $servicesFileName;
                }
                $camelized = $this->camelize($extension_name);
                $name = "{$camelized}ServiceProvider";
                $class = "Drupal\\{$extension_name}\\{$name}";
                $this->serviceClassProviders[$extension_name] = $class;
                $serviceId = "service_provider.$extension_name.service_provider";
                $this->serviceMap[$serviceId] = ['class' => $class];
            }
            if ($type === 'theme') {
                $theme_settings_file = $extension_path . '/theme-settings.php';
                if (file_exists($theme_settings_file)) {
                    $this->loadAndCatchErrors($theme_settings_file);
                }
            }
        }

        // Iterate over hook_hook_info implementations and load those files.
        if (count($hook_info_implementations) > 0) {
            $hook_info_implementations = array_merge(...$hook_info_implementations);
            foreach ($hook_info_implementations as $hook_info_group) {
                foreach ($extensions as $extension) {
                    $include_file = $this->drupalRoot . '/' . $extension->getPath() . '/' . $extension->getName() . '.' . $hook_info_group . '.inc';
                    if (file_exists($include_file)) {
                        $this->loadAndCatchErrors($include_file);
                    }
                }
            }
        }
    }

    /**
     * @see drupal_phpunit_populate_class_loader
     */
    private function registerTestNamespaces(): void
    {
        // Start with classes in known locations.
        $dir = $this->drupalRoot . '/core/tests';
        $this->autoloader->add('Drupal\\BuildTests', $dir);
        $this->autoloader->add('Drupal\\Tests', $dir);
        $this->autoloader->add('Drupal\\TestSite', $dir);
        $this->autoloader->add('Drupal\\KernelTests', $dir);
        $this->autoloader->add('Drupal\\FunctionalTests', $dir);
        $this->autoloader->add('Drupal\\FunctionalJavascriptTests', $dir);
        $this->autoloader->add('Drupal\\TestTools', $dir);
        $this->autoloader->addPsr4('Drupal\\Tests\\TestSuites\\', $this->drupalRoot . '/core/tests/TestSuites');
    }

    private function loadDrushIncludes(): void
    {
        if (class_exists(\Drush\Drush::class)) {
            $reflect = new \ReflectionClass(\Drush\Drush::class);
            if ($reflect->getFileName() !== false) {
                $levels = 2;
                if (\Drush\Drush::getMajorVersion() < 9) {
                    $levels = 3;
                }
                $drushDir = dirname($reflect->getFileName(), $levels);
                $this->loadIncludesByDirectory('drush/drush', $drushDir);
            }
        }
    }

    private function loadIncludesByDirectory(string $package, string $absolute_path): void
    {
        $flags = \FilesystemIterator::UNIX_PATHS;
        $flags |= \FilesystemIterator::SKIP_DOTS;
        $flags |= \FilesystemIterator::FOLLOW_SYMLINKS;
        $flags |= \FilesystemIterator::CURRENT_AS_SELF;
        $directory_iterator = new \RecursiveDirectoryIterator($absolute_path, $flags);
        $iterator = new \RecursiveIteratorIterator(
            $directory_iterator,
            \RecursiveIteratorIterator::LEAVES_ONLY,
            // Suppress filesystem errors in case a directory cannot be accessed.
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($iterator as $fileinfo) {
            // Check if this file was added as an autoloaded file in a
            // composer.json file.
            //
            // @see \Composer\Autoload\AutoloadGenerator::getFileIdentifier().
            $autoloadFileIdentifier = md5($package . ':includes/' . $fileinfo->getFilename());
            if (isset($GLOBALS['__composer_autoload_files'][$autoloadFileIdentifier])) {
                continue;
            }
            if ($fileinfo->getExtension() === 'inc') {
                require_once $fileinfo->getPathname();
            }
        }
    }
}
