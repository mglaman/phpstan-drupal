<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use DrupalFinder\DrupalFinderComposerRuntime;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use function array_map;
use function array_merge;
use function array_values;
use function file_exists;
use function is_array;
use function is_dir;
use function is_string;
use function str_contains;
use function usort;

/**
 * Reads what a Drupal site has on disk, without running the bootstrap.
 *
 * PHPStan's result cache extensions run before bootstrap files, so the state
 * the bootstrap sets on ServiceMap, ExtensionMap, and ConfigSchemaData is empty
 * when PHPStan restores the cache. Anything that decides whether a result is
 * still valid has to read from here instead.
 */
class DrupalSite
{

    private ?string $root = null;

    /** @var list<Extension>|null */
    private ?array $modules = null;

    /** @var array<string, Extension> */
    private array $themes = [];

    /** @var array<string, Extension> */
    private array $profiles = [];

    /** @var array<string, mixed>|null */
    private ?array $serviceDefinitions = null;

    public function getRoot(): string
    {
        if ($this->root === null) {
            $root = (new DrupalFinderComposerRuntime())->getDrupalRoot();
            if (!is_string($root)) {
                throw new RuntimeException("Unable to detect Drupal with webflo/drupal-finder.");
            }
            $this->root = $root;
        }
        return $this->root;
    }

    /**
     * The modules and profiles, in the order the bootstrap loads them.
     *
     * @return list<Extension>
     */
    public function getModules(): array
    {
        $this->discover();
        return $this->modules ?? [];
    }

    /**
     * @return array<string, Extension>
     */
    public function getThemes(): array
    {
        $this->discover();
        return $this->themes;
    }

    /**
     * @return array<string, Extension>
     */
    public function getProfiles(): array
    {
        $this->discover();
        return $this->profiles;
    }

    /**
     * The raw entries of every services.yml file, keyed by service ID.
     *
     * A later file overrides an earlier one, as in the Drupal container.
     *
     * @return array<string, mixed>
     */
    public function getServiceDefinitions(): array
    {
        if ($this->serviceDefinitions === null) {
            $this->serviceDefinitions = [];
            foreach ($this->getServiceYamlPaths() as $path) {
                $yaml = Yaml::parseFile($path, Yaml::PARSE_CUSTOM_TAGS);
                // Weed out service files which only provide parameters.
                if (!isset($yaml['services']) || !is_array($yaml['services'])) {
                    continue;
                }
                foreach ($yaml['services'] as $serviceId => $definition) {
                    $this->serviceDefinitions[(string) $serviceId] = $definition;
                }
            }
        }
        return $this->serviceDefinitions;
    }

    /**
     * @return list<string>
     */
    public function getSchemaDirectories(): array
    {
        $root = $this->getRoot();
        $directories = [];
        if (is_dir($root . '/core/config/schema')) {
            $directories[] = $root . '/core/config/schema';
        }
        foreach (array_merge($this->getModules(), array_values($this->getThemes())) as $extension) {
            $directory = $root . '/' . $extension->getPath() . '/config/schema';
            if (is_dir($directory)) {
                $directories[] = $directory;
            }
        }
        return $directories;
    }

    /**
     * @return list<string>
     */
    private function getServiceYamlPaths(): array
    {
        $root = $this->getRoot();
        $paths = [$root . '/core/core.services.yml'];
        foreach ($this->getModules() as $module) {
            $path = $root . '/' . $module->getPath() . '/' . $module->getName() . '.services.yml';
            if (file_exists($path)) {
                $paths[] = $path;
            }
        }
        return $paths;
    }

    private function discover(): void
    {
        if ($this->modules !== null) {
            return;
        }
        $extensionDiscovery = new ExtensionDiscovery($this->getRoot());
        $extensionDiscovery->setProfileDirectories([]);
        $this->profiles = $extensionDiscovery->scan('profile');
        $extensionDiscovery->setProfileDirectories(array_map(static function (Extension $profile): string {
            return $profile->getPath();
        }, $this->profiles));

        $modules = array_values(array_merge($extensionDiscovery->scan('module'), $this->profiles));
        // Load test extensions after regular ones. Test modules stub functions
        // from their parent module behind function_exists() guards, so if the
        // test module's .module file loads first the parent's unconditional
        // declaration is a compile error that loadAndCatchErrors() cannot
        // intercept.
        usort($modules, static function (Extension $a, Extension $b): int {
            return str_contains($a->getName(), '_test') <=> str_contains($b->getName(), '_test');
        });
        $this->modules = $modules;
        $this->themes = $extensionDiscovery->scan('theme');
    }
}
