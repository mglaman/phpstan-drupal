<?php declare(strict_types=1);

namespace PHPStan\Drupal;

use Drupal\Core\Extension\Discovery\RecursiveExtensionFilterIterator;
use Drupal\Core\Extension\Extension;

class ExtensionDiscovery {

	/**
	 * Origin directory weight: Core.
	 */
	private const ORIGIN_CORE = 0;

	/**
	 * Origin directory weight: Installation profile.
	 */
	private const ORIGIN_PROFILE = 1;

	/**
	 * Origin directory weight: sites/all.
	 */
	private const ORIGIN_SITES_ALL = 2;

	/**
	 * Origin directory weight: Site-wide directory.
	 */
	private const ORIGIN_ROOT = 3;

	/**
	 * Origin directory weight: Parent site directory of a test site environment.
	 */
	private const ORIGIN_PARENT_SITE = 4;

	/**
	 * Origin directory weight: Site-specific directory.
	 */
	private const ORIGIN_SITE = 5;

	/**
	 * Regular expression to match PHP function names.
	 *
	 * @see http://php.net/manual/functions.user-defined.php
	 */
	private const PHP_FUNCTION_PATTERN = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

	/**
	 * Previously discovered files keyed by origin directory and extension type.
	 *
	 * @var array
	 */
	protected static $files = [];

	/**
	 * List of installation profile directories to additionally scan.
	 *
	 * @var array
	 */
	protected $profileDirectories;

	/**
	 * The app root for the current operation.
	 *
	 * @var string
	 */
	protected $root;

	/**
	 * The file cache object.
	 *
	 * @var \Drupal\Component\FileCache\FileCacheInterface
	 */
	protected $fileCache;

	/**
	 * The site path.
	 *
	 * @var string
	 */
	protected $sitePath;

	/**
	 * Constructs a new ExtensionDiscovery object.
	 *
	 * @param string $root
	 *   The app root.
	 * @param bool $use_file_cache
	 *   Whether file cache should be used.
	 * @param string[] $profile_directories
	 *   The available profile directories
	 * @param string $site_path
	 *   The path to the site.
	 */
	public function __construct($root) {
		$this->root = $root;
		$this->fileCache = NULL;
		$this->profileDirectories = [
			$root . '/core/profiles/standard'
		];
		$this->sitePath = 'sites/default';
	}

	/**
	 * Discovers available extensions of a given type.
	 *
	 * Finds all extensions (modules, themes, etc) that exist on the site. It
	 * searches in several locations. For instance, to discover all available
	 * modules:
	 * @code
	 * $listing = new ExtensionDiscovery(\Drupal::root());
	 * $modules = $listing->scan('module');
	 * @endcode
	 *
	 * The following directories will be searched (in the order stated):
	 * - the core directory; i.e., /core
	 * - the installation profile directory; e.g., /core/profiles/standard
	 * - the legacy site-wide directory; i.e., /sites/all
	 * - the site-wide directory; i.e., /
	 * - the site-specific directory; e.g., /sites/example.com
	 *
	 * To also find test modules, add
	 * @code
	 * $settings['extension_discovery_scan_tests'] = TRUE;
	 * @endcode
	 * to your settings.php.
	 *
	 * The information is returned in an associative array, keyed by the extension
	 * name (without .info.yml extension). Extensions found later in the search
	 * will take precedence over extensions found earlier - unless they are not
	 * compatible with the current version of Drupal core.
	 *
	 * @param string $type
	 *   The extension type to search for. One of 'profile', 'module', 'theme', or
	 *   'theme_engine'.
	 *
	 * @return \Drupal\Core\Extension\Extension[]
	 *   An associative array of Extension objects, keyed by extension name.
	 */
	public function scan($type) {
		// Search the core directory.
		$searchdirs[static::ORIGIN_CORE] = 'core';

		// Search the legacy sites/all directory.
		$searchdirs[static::ORIGIN_SITES_ALL] = 'sites/all';

		// Search for contributed and custom extensions in top-level directories.
		// The scan uses a whitelist to limit recursion to the expected extension
		// type specific directory names only.
		$searchdirs[static::ORIGIN_ROOT] = '';

		$searchdirs[static::ORIGIN_SITE] = $this->sitePath;

		$files = [];
		foreach ($searchdirs as $dir) {
			// Discover all extensions in the directory, unless we did already.
			if (!isset(static::$files[$this->root][$dir])) {
				static::$files[$this->root][$dir] = $this->scanDirectory($dir);
			}
			// Only return extensions of the requested type.
			if (isset(static::$files[$this->root][$dir][$type])) {
				$files += static::$files[$this->root][$dir][$type];
			}
		}

		// If applicable, filter out extensions that do not belong to the current
		// installation profiles.
		$files = $this->filterByProfileDirectories($files);
		// Sort the discovered extensions by their originating directories.
		$origin_weights = array_flip($searchdirs);
		$files = $this->sort($files, $origin_weights);

		// Process and return the list of extensions keyed by extension name.
		return $this->process($files);
	}

	/**
	 * Gets the installation profile directories to be scanned.
	 *
	 * @return array
	 *   A list of installation profile directory paths relative to the system
	 *   root directory.
	 */
	public function getProfileDirectories() {
		return $this->profileDirectories;
	}

	/**
	 * Sets explicit profile directories to scan.
	 *
	 * @param array $paths
	 *   A list of installation profile directory paths relative to the system
	 *   root directory (without trailing slash) to search for extensions.
	 *
	 * @return $this
	 */
	public function setProfileDirectories(array $paths = NULL) {
		$this->profileDirectories = $paths;
		return $this;
	}

	/**
	 * Filters out extensions not belonging to the scanned installation profiles.
	 *
	 * @param \Drupal\Core\Extension\Extension[] $all_files
	 *   The list of all extensions.
	 *
	 * @return \Drupal\Core\Extension\Extension[]
	 *   The filtered list of extensions.
	 */
	protected function filterByProfileDirectories(array $all_files) {
		if (empty($this->profileDirectories)) {
			return $all_files;
		}

		$all_files = array_filter($all_files, function ($file) {
			if (strpos($file->subpath, 'profiles') !== 0) {
				// This extension doesn't belong to a profile, ignore it.
				return TRUE;
			}

			foreach ($this->profileDirectories as $weight => $profile_path) {
				if (strpos($file->getPath(), $profile_path) === 0) {
					// Parent profile found.
					return TRUE;
				}
			}

			return FALSE;
		});

		return $all_files;
	}

	/**
	 * Sorts the discovered extensions.
	 *
	 * @param \Drupal\Core\Extension\Extension[] $all_files
	 *   The list of all extensions.
	 * @param array $weights
	 *   An array of weights, keyed by originating directory.
	 *
	 * @return \Drupal\Core\Extension\Extension[]
	 *   The sorted list of extensions.
	 */
	protected function sort(array $all_files, array $weights) {
		$origins = [];
		$profiles = [];
		foreach ($all_files as $key => $file) {
			// If the extension does not belong to a profile, just apply the weight
			// of the originating directory.
			if (strpos($file->subpath, 'profiles') !== 0) {
				$origins[$key] = $weights[$file->origin];
				$profiles[$key] = NULL;
			}
			// If the extension belongs to a profile but no profile directories are
			// defined, then we are scanning for installation profiles themselves.
			// In this case, profiles are sorted by origin only.
			elseif (empty($this->profileDirectories)) {
				$origins[$key] = static::ORIGIN_PROFILE;
				$profiles[$key] = NULL;
			}
			else {
				// Apply the weight of the originating profile directory.
				foreach ($this->profileDirectories as $weight => $profile_path) {
					if (strpos($file->getPath(), $profile_path) === 0) {
						$origins[$key] = static::ORIGIN_PROFILE;
						$profiles[$key] = $weight;
						continue 2;
					}
				}
			}
		}
		// Now sort the extensions by origin and installation profile(s).
		// The result of this multisort can be depicted like the following matrix,
		// whereas the first integer is the weight of the originating directory and
		// the second is the weight of the originating installation profile:
		// 0   core/modules/node/node.module
		// 1 0 profiles/parent_profile/modules/parent_module/parent_module.module
		// 1 1 core/profiles/testing/modules/compatible_test/compatible_test.module
		// 2   sites/all/modules/common/common.module
		// 3   modules/devel/devel.module
		// 4   sites/default/modules/custom/custom.module
		array_multisort($origins, SORT_ASC, $profiles, SORT_ASC, $all_files);

		return $all_files;
	}

	/**
	 * Processes the filtered and sorted list of extensions.
	 *
	 * Extensions discovered in later search paths override earlier, unless they
	 * are not compatible with the current version of Drupal core.
	 *
	 * @param \Drupal\Core\Extension\Extension[] $all_files
	 *   The sorted list of all extensions that were found.
	 *
	 * @return \Drupal\Core\Extension\Extension[]
	 *   The filtered list of extensions, keyed by extension name.
	 */
	protected function process(array $all_files) {
		$files = [];
		// Duplicate files found in later search directories take precedence over
		// earlier ones; they replace the extension in the existing $files array.
		foreach ($all_files as $file) {
			$files[$file->getName()] = $file;
		}
		return $files;
	}

	/**
	 * Recursively scans a base directory for the extensions it contains.
	 *
	 * @param string $dir
	 *   A relative base directory path to scan, without trailing slash.
	 *
	 * @return array
	 *   An associative array whose keys are extension type names and whose values
	 *   are associative arrays of \Drupal\Core\Extension\Extension objects, keyed
	 *   by absolute path name.
	 *
	 * @see \Drupal\Core\Extension\Discovery\RecursiveExtensionFilterIterator
	 */
	protected function scanDirectory($dir) {
		$files = [];

		// In order to scan top-level directories, absolute directory paths have to
		// be used (which also improves performance, since any configured PHP
		// include_paths will not be consulted). Retain the relative originating
		// directory being scanned, so relative paths can be reconstructed below
		// (all paths are expected to be relative to $this->root).
		$dir_prefix = ($dir === '' ? '' : "$dir/");
		$absolute_dir = ($dir === '' ? $this->root : $this->root . "/$dir");

		if (!is_dir($absolute_dir)) {
			return $files;
		}
		// Use Unix paths regardless of platform, skip dot directories, follow
		// symlinks (to allow extensions to be linked from elsewhere), and return
		// the RecursiveDirectoryIterator instance to have access to getSubPath(),
		// since SplFileInfo does not support relative paths.
		$flags = \FilesystemIterator::UNIX_PATHS;
		$flags |= \FilesystemIterator::SKIP_DOTS;
		$flags |= \FilesystemIterator::FOLLOW_SYMLINKS;
		$flags |= \FilesystemIterator::CURRENT_AS_SELF;
		$directory_iterator = new \RecursiveDirectoryIterator($absolute_dir, $flags);

		// Allow directories specified in settings.php to be ignored. You can use
		// this to not check for files in common special-purpose directories. For
		// example, node_modules and bower_components. Ignoring irrelevant
		// directories is a performance boost.
		$ignore_directories = ['node_modules', 'bower_components'];

		// Filter the recursive scan to discover extensions only.
		// Important: Without a RecursiveFilterIterator, RecursiveDirectoryIterator
		// would recurse into the entire filesystem directory tree without any kind
		// of limitations.
		$filter = new RecursiveExtensionFilterIterator($directory_iterator, $ignore_directories);
		$filter->acceptTests(TRUE);

		// The actual recursive filesystem scan is only invoked by instantiating the
		// RecursiveIteratorIterator.
		$iterator = new \RecursiveIteratorIterator($filter,
			\RecursiveIteratorIterator::LEAVES_ONLY,
			// Suppress filesystem errors in case a directory cannot be accessed.
			\RecursiveIteratorIterator::CATCH_GET_CHILD
		);

		foreach ($iterator as $key => $fileinfo) {
			// All extension names in Drupal have to be valid PHP function names due
			// to the module hook architecture.
			if (!preg_match(static::PHP_FUNCTION_PATTERN, $fileinfo->getBasename('.info.yml'))) {
				continue;
			}

			if ($this->fileCache && $cached_extension = $this->fileCache->get($fileinfo->getPathName())) {
				$files[$cached_extension->getType()][$key] = $cached_extension;
				continue;
			}

			// Determine extension type from info file.
			$type = FALSE;
			$file = $fileinfo->openFile('r');
			while (!$type && !$file->eof()) {
				preg_match('@^type:\s*(\'|")?(\w+)\1?\s*$@', $file->fgets(), $matches);
				if (isset($matches[2])) {
					$type = $matches[2];
				}
			}
			if (empty($type)) {
				continue;
			}
			$name = $fileinfo->getBasename('.info.yml');
			$pathname = $dir_prefix . $fileinfo->getSubPathname();

			// Determine whether the extension has a main extension file.
			// For theme engines, the file extension is .engine.
			if ($type === 'theme_engine') {
				$filename = $name . '.engine';
			}
			// For profiles/modules/themes, it is the extension type.
			else {
				$filename = $name . '.' . $type;
			}
			if (!file_exists($this->root . '/' . dirname($pathname) . '/' . $filename)) {
				$filename = NULL;
			}

			$extension = new Extension($this->root, $type, $pathname, $filename);

			// Track the originating directory for sorting purposes.
			$extension->subpath = $fileinfo->getSubPath();
			$extension->origin = $dir;

			$files[$type][$key] = $extension;

			if ($this->fileCache) {
				$this->fileCache->set($fileinfo->getPathName(), $extension);
			}
		}
		return $files;
	}


}
