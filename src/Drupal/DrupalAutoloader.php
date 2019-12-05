<?php declare(strict_types=1);

namespace PHPStan\Drupal;

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use DrupalFinder\DrupalFinder;
use Nette\Utils\Finder;
use PHPStan\DependencyInjection\Container;

class DrupalAutoloader
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
     * @var array
     */
    private $modules = [];

    /**
     * @var array
     */
    private $themes = [];

    /**
     * @var ?\PHPStan\Drupal\ExtensionDiscovery
     */
    private $extensionDiscovery;

    /**
     * @var array
     */
    private $namespaces = [];

    public function register(Container $container): void
    {
        $startPath = null;
        if ($container->hasParameter('drupal_root')) {
            $drupalRoot = $container->getParameter('drupal_root');
            if (realpath($drupalRoot) !== false && is_dir($drupalRoot)) {
                $startPath = $drupalRoot;
            }
        }
        if ($startPath === null) {
            $startPath = dirname($GLOBALS['autoloaderInWorkingDirectory']);
        }
        $finder = new DrupalFinder();
        $finder->locateRoot($startPath);

        $drupalRoot = $finder->getDrupalRoot();
        $drupalVendorRoot = $finder->getVendorDir();
        if (! (bool) $drupalRoot || ! (bool) $drupalVendorRoot) {
            throw new \RuntimeException("Unable to detect Drupal at $startPath");
        }

        $this->drupalRoot = $drupalRoot;

        $this->autoloader = include $drupalVendorRoot . '/autoload.php';

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
                $suite_names = ['Unit', 'Kernel', 'Functional', 'FunctionalJavascript', 'Build'];
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
}
