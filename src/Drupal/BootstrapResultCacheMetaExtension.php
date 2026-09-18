<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use Drupal;
use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;
use function class_exists;
use function glob;
use function hash;
use function hash_file;
use function ksort;
use function serialize;
use function sort;

/**
 * Invalidates PHPStan's result cache when the analyzed Drupal site changes.
 *
 * PHPStan hashes its bootstrap files, but not the Drupal extensions and
 * services.yml files the bootstrap discovers. Without this extension,
 * enabling a module, editing a services.yml, or upgrading core would reuse
 * stale analysis results from the cache.
 */
final class BootstrapResultCacheMetaExtension implements ResultCacheMetaExtension
{

    public function __construct(
        private readonly ServiceMap $serviceMap,
        private readonly ExtensionMap $extensionMap,
        private readonly ConfigSchemaData $configSchemaData
    ) {
    }

    public function getKey(): string
    {
        return 'phpstan-drupal';
    }

    public function getHash(): string
    {
        $extensions = [];
        $groups = [
            'module' => $this->extensionMap->getModules(),
            'theme' => $this->extensionMap->getThemes(),
            'profile' => $this->extensionMap->getProfiles(),
        ];
        foreach ($groups as $type => $group) {
            ksort($group);
            foreach ($group as $name => $extension) {
                $infoPath = $extension->getAbsolutePath() . DIRECTORY_SEPARATOR . $extension->getFilename();
                $extensions[$type][$name] = [$infoPath, $this->hashFile($infoPath)];
            }
        }

        $services = [];
        $serviceYamlPaths = $this->serviceMap->getServiceYamlPaths();
        ksort($serviceYamlPaths);
        foreach ($serviceYamlPaths as $path) {
            $services[$path] = $this->hashFile($path);
        }

        $schemas = [];
        $schemaDirectories = $this->configSchemaData->getSchemaDirectories();
        sort($schemaDirectories);
        foreach ($schemaDirectories as $directory) {
            $files = glob($directory . '/*.schema.yml');
            if ($files === false) {
                continue;
            }
            foreach ($files as $file) {
                $schemas[$file] = $this->hashFile($file);
            }
        }

        return hash('sha256', serialize([
            'drupal' => class_exists(Drupal::class) ? Drupal::VERSION : 'unknown',
            'extensions' => $extensions,
            'services' => $services,
            'schemas' => $schemas,
        ]));
    }

    private function hashFile(string $path): string
    {
        $hash = @hash_file('sha256', $path);
        // A file that disappeared after discovery must still change the hash.
        return $hash === false ? 'missing' : $hash;
    }
}
