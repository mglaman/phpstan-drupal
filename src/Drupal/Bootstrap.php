<?php declare(strict_types=1);

namespace PHPStan\Drupal;

use Composer\Autoload\ClassLoader;

class Bootstrap
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
     * @var array
     */
    private $modules = [];

    public function __construct()
    {
        $autoload_file = $GLOBALS['composerAutoloadFile'];
        /** @noinspection PhpIncludeInspection */
        $this->autoloader = require $autoload_file;
        if (!$this->autoloader instanceof ClassLoader) {
            throw new \InvalidArgumentException('Unable to determine the Composer class loader for Drupal');
        }

        $realpath = realpath($autoload_file);
        if ($realpath === false) {
            throw new \InvalidArgumentException('Cannot determine the realpath of the autoloader.');
        }
        $project_root = dirname($realpath, 2);
        if (is_dir($project_root . '/core')) {
            $this->drupalRoot = $project_root;
        }
        foreach (['web', 'docroot'] as $possible_docroot) {
            if (is_dir("$project_root/$possible_docroot/core")) {
                $this->drupalRoot = "$project_root/$possible_docroot";
            }
        }
        if ($this->drupalRoot === null) {
            throw new \InvalidArgumentException('Unable to determine the Drupal root');
        }
    }

    public function register(): void
    {
        require $this->drupalRoot . '/core/includes/bootstrap.inc';
        require $this->drupalRoot . '/core/includes/common.inc';
        require $this->drupalRoot . '/core/includes/entity.inc';
        require $this->drupalRoot . '/core/includes/menu.inc';
        require $this->drupalRoot . '/core/includes/database.inc';
        require $this->drupalRoot . '/core/includes/file.inc';
        $core_namespaces = $this->getCoreNamespaces();
        $module_namespaces = $this->loadModules();

        $namespaces = array_merge($core_namespaces, $module_namespaces);

        foreach ($namespaces as $prefix => $paths) {
            if (is_array($paths)) {
                foreach ($paths as $key => $value) {
                    $paths[$key] = $value;
                }
            }
            $this->autoloader->addPsr4($prefix . '\\', $paths);
        }

        // Add core test namespaces.
        $core_tests_dir = $this->drupalRoot . '/core/tests';
        $this->autoloader->add('Drupal\\Tests', $core_tests_dir);
        $this->autoloader->add('Drupal\\TestSite', $core_tests_dir);
        $this->autoloader->add('Drupal\\KernelTests', $core_tests_dir);
        $this->autoloader->add('Drupal\\FunctionalTests', $core_tests_dir);
        $this->autoloader->add('Drupal\\FunctionalJavascriptTests', $core_tests_dir);
    }

    /**
     * @return array
     *
     * @see \Drupal\Core\DrupalKernel::compileContainer
     */
    protected function getCoreNamespaces(): array
    {
        $namespaces = [];
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
                    $namespaces[$parent_namespace . '\\' . $component->getFilename()] = $path . '/' . $component->getFilename();
                }
            }
        }

        return $namespaces;
    }

    protected function loadModules(): array
    {
        $listing = new ExtensionDiscovery($this->drupalRoot);
        $listing->setProfileDirectories([]);
        $profiles = $listing->scan('profile');
        $profile_directories = array_map(function ($profile) {
            return $profile->getPath();
        }, $profiles);
        $listing->setProfileDirectories($profile_directories);

        $this->modules = $listing->scan('module');
        $namespaces = [];
        foreach ($this->modules as $module_name => $module) {
            $module_dir = $this->drupalRoot . '/' . $module->getPath();
            $namespaces["Drupal\\$module_name"] = $module_dir . '/src';

            // @see drupal_phpunit_get_extension_namespaces
            $module_test_dir = $module_dir . '/tests/src';
            if (is_dir($module_test_dir)) {
                $suite_names = ['Unit', 'Kernel', 'Functional', 'FunctionalJavascript'];
                foreach ($suite_names as $suite_name) {
                    $suite_dir = $module_test_dir . '/' . $suite_name;
                    if (is_dir($suite_dir)) {
                        // Register the PSR-4 directory for PHPUnit-based suites.
                        $namespaces["Drupal\\Tests\\$module_name\\$suite_name"] = $suite_dir;
                    }

                    // Extensions can have a \Drupal\extension\Traits namespace for
                    // cross-suite trait code.
                    $trait_dir = $module_test_dir . '/Traits';
                    if (is_dir($trait_dir)) {
                        $namespaces["Drupal\\Tests\\$module_name\\Traits"] = $trait_dir;
                    }
                }
            }
            // Need to ensure .module is enabled.
            if ($module->getExtensionFilename() !== null) {
                require $module_dir . '/' . $module->getExtensionFilename();
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

        return $namespaces;
    }
}
