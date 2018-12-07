<?php declare(strict_types=1);

namespace PHPStan\Drupal;

use Composer\Autoload\ClassLoader;

class Bootstrap {

	/**
	 * @var \Composer\Autoload\ClassLoader
	 */
	private $autoloader;

	private $drupalRoot;

	public function __construct() {
		$autoload_file = $GLOBALS['composerAutoloadFile'];
		/** @noinspection PhpIncludeInspection */
		$this->autoloader = require $autoload_file;
		if (!$this->autoloader instanceof ClassLoader) {
			throw new \InvalidArgumentException('Unable to determine the Composer class loader for Drupal');
		}

		$project_root = dirname(realpath($autoload_file), 2);
		if (is_dir($project_root . '/core')) {
			$this->drupalRoot = $project_root;
		}
		foreach (['web', 'docroot'] as $possible_docroot) {
			if (is_dir("$project_root/$possible_docroot/core")) {
				$this->drupalRoot = "$project_root/$possible_docroot";
			}
		}
		if ($this->drupalRoot === NULL) {
			throw new \InvalidArgumentException('Unable to determine the Drupal root');
		}
	}

	public function register(): void {
		$core_namespaces = $this->getCoreNamespaces();
		$module_namespaces = $this->getModuleNamespaces();

		$namespaces = array_merge($core_namespaces, $module_namespaces);

		foreach ($namespaces as $prefix => $paths) {
			if (is_array($paths)) {
				foreach ($paths as $key => $value) {
					$paths[$key] = $value;
				}
			}
			$this->autoloader->addPsr4($prefix . '\\', $paths);
		}
	}

	/**
	 * @return array
	 *
	 * @see \Drupal\Core\DrupalKernel::compileContainer
	 */
	protected function getCoreNamespaces(): array {
		$namespaces = [];
		foreach (['Core', 'Component'] as $parent_directory) {
			$path = $this->drupalRoot . '/core/lib/Drupal/' . $parent_directory;
			$parent_namespace = 'Drupal\\' . $parent_directory;
			foreach (new \DirectoryIterator($path) as $component) {
				/** @var $component \DirectoryIterator */
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

	protected function getModuleNamespaces(): array {
		// @todo inspect root level modules in core/modules and modules/contrib
		$modules = [
			'commerce',
			// @todo submodules have incorrect paths.
			'commerce_price',
			'commerce_store',
			'views',
			'user',
			'datetime',
		];
		foreach ($modules as $module) {
			if (is_dir($this->drupalRoot . '/core/modules/' . $module)) {
				$module_path = $this->drupalRoot . '/core/modules/' . $module;
			}
			elseif (is_dir($this->drupalRoot . '/modules/contrib/' . $module)) {
				$module_path = $this->drupalRoot . '/modules/contrib/' . $module;
			}
			else {
				continue;
			}
			$namespaces["Drupal\\$module"] = $module_path . '/src';
		}

		return $namespaces;
	}

}
