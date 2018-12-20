<?php declare(strict_types=1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\DI\Config\Helpers;
use PHPStan\Drupal\ExtensionDiscovery;
use PHPStan\Rules\Classes\EnhancedRequireParentConstructCallRule;
use PHPStan\Rules\Classes\RequireParentConstructCallRule;

class DrupalExtension extends CompilerExtension
{
    /**
     * @var array
     */
    protected $defaultConfig = [
        'modules' => [],
        'themes' => [],
    ];

    /**
     * @var string
     */
    private $autoloaderPath;

    /**
     * @var string
     */
    private $drupalRoot;

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

        $this->autoloaderPath = $GLOBALS['autoloaderInWorkingDirectory'];
        $realpath = realpath($this->autoloaderPath);
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

        $builder = $this->getContainerBuilder();
        $builder->parameters['drupalRoot'] = $this->drupalRoot;

        $config = Helpers::merge($this->config, $this->defaultConfig);

        $this->modules = $config['modules'] ?? [];
        $this->themes = $config['themes'] ?? [];

        $builder->parameters['drupal']['entityTypeStorageMapping'] = $config['entityTypeStorageMapping'];

        $builder = $this->getContainerBuilder();
        foreach ($builder->getDefinitions() as $definition) {
            $factory = $definition->getFactory();
            if ($factory === null) {
                continue;
            }
            if ($factory->entity === RequireParentConstructCallRule::class) {
                $definition->setFactory(EnhancedRequireParentConstructCallRule::class);
            }
        }

        // Build the service definitions...
        $extensionDiscovery = new ExtensionDiscovery($this->drupalRoot);
        $extensionDiscovery->setProfileDirectories([]);
        $profiles = $extensionDiscovery->scan('profile');
        $profile_directories = array_map(function ($profile) {
            return $profile->getPath();
        }, $profiles);
        $extensionDiscovery->setProfileDirectories($profile_directories);

        foreach ($extensionDiscovery->scan('module') as $extension) {
            $module_dir = $this->drupalRoot . '/' . $extension->getPath();
            $servicesFileName = $module_dir . '/' . $extension->getName() . '.services.yml';
            if (file_exists($servicesFileName)) {
                // @todo load and parse, push basic definitions into container parameters
            }
        }
    }
}
