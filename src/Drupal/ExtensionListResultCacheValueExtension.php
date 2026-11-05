<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;
use function hash;
use function ksort;
use function serialize;

/**
 * Re-analyzes every file when an extension is added, removed, or moved.
 *
 * The bootstrap registers an autoload namespace for each extension, so the
 * list decides which classes exist for all analyzed code. Paths are relative to
 * the Drupal root so that the cache survives moving the project.
 */
final class ExtensionListResultCacheValueExtension implements ResultCacheValueExtension
{

    public const KEY = 'extensions';

    public function __construct(
        private readonly DrupalSite $site
    ) {
    }

    public function getValue(string $key): string
    {
        return hash('sha256', serialize([
            'module' => $this->getPaths($this->site->getModules()),
            'theme' => $this->getPaths($this->site->getThemes()),
            'profile' => $this->getPaths($this->site->getProfiles()),
        ]));
    }

    public function keyToResultCache(string $key): string
    {
        return $key;
    }

    public function keyFromResultCache(string $storedKey): string
    {
        return $storedKey;
    }

    /**
     * @param Extension[] $extensions
     * @return array<string, string>
     */
    private function getPaths(array $extensions): array
    {
        $paths = [];
        foreach ($extensions as $extension) {
            $paths[$extension->getName()] = $extension->getPath();
        }
        ksort($paths);
        return $paths;
    }
}
