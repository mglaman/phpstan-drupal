<?php declare(strict_types=1);

namespace PHPStan\Drupal;

use DrupalFinder\DrupalFinder;
use Nette\Utils\Finder;

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

    public function register(): void
    {
        $finder = new DrupalFinder();
        $finder->locateRoot(dirname($GLOBALS['autoloaderInWorkingDirectory'], 2));
        $this->autoloader = include $finder->getVendorDir() . '/autoload.php';
        $this->drupalRoot = $finder->getDrupalRoot();

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

        $this->loadLegacyIncludes();

        $this->addCoreNamespaces();
        $this->addModuleNamespaces();
        $this->addThemeNamespaces();
        $this->registerPs4Namespaces($this->namespaces);

        foreach ($this->moduleData as $extension) {
            $this->loadExtension($extension);

            $module_name = $extension->getName();
            $module_dir = $this->drupalRoot . '/' . $extension->getPath();
            // Add .install
            if (file_exists($module_dir . '/' . $module_name . '.install')) {
                // Causes errors on Drupal container not initialized
                // require $module_dir . '/' . $module_name . '.install';
            }
            // Add .post_update.php
            if (file_exists($module_dir . '/' . $module_name . '.post_update.php')) {
                require $module_dir . '/' . $module_name . '.post_update.php';
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
                    require $module_dir . "/$module_name.$hook_info_include.inc";
                }
            }
        }
        foreach ($this->themeData as $extension) {
            $this->loadExtension($extension);
        }
    }

    protected function loadLegacyIncludes(): void
    {
        /** @var \SplFileInfo $file */
        foreach (Finder::findFiles('*.inc')->in($this->drupalRoot . '/core/includes') as $file) {
            require $file->getPathname();
        }
    }

    protected function addCoreNamespaces(): void
    {
        foreach (['Core', 'Component'] as $parent_directory) {
            $path = $this->drupalRoot . '/core/lib/Drupal/' . $parent_directory;
            $parent_namespace = 'Drupal\\' . $parent_directory;
            foreach (new \DirectoryIterator($path) as $component) {
                $pathname = $component->getPathname();
                if (!$component->isDot() && $component->isDir() && (
                        is_dir($pathname . '/Plugin') ||
                        is_dir($pathname . '/Entity') ||
                        is_dir($pathname . '/Element')
                    )) {
                    $this->namespaces[$parent_namespace . '\\' . $component->getFilename()] = $path . '/' . $component->getFilename();
                }
            }
        }

        // Add core test namespaces.
        $core_tests_dir = $this->drupalRoot . '/core/tests';
        $this->autoloader->add('Drupal\\Tests', $core_tests_dir);
        $this->autoloader->add('Drupal\\TestSite', $core_tests_dir);
        $this->autoloader->add('Drupal\\KernelTests', $core_tests_dir);
        $this->autoloader->add('Drupal\\FunctionalTests', $core_tests_dir);
        $this->autoloader->add('Drupal\\FunctionalJavascriptTests', $core_tests_dir);
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
}
