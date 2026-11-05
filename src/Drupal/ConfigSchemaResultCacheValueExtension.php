<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;
use function glob;
use function hash;
use function hash_file;
use function ksort;
use function serialize;

/**
 * Re-analyzes the files reading config schema when any schema file changes.
 *
 * Schema types reference each other across files, so a config object can't be
 * mapped to its files without parsing all of them. Hashing the files keeps this
 * cheap on every run, including for sites that don't enable the schema features.
 */
final class ConfigSchemaResultCacheValueExtension implements ResultCacheValueExtension
{

    public const KEY = 'schema';

    public function __construct(
        private readonly DrupalSite $site
    ) {
    }

    public function getValue(string $key): string
    {
        $hashes = [];
        foreach ($this->site->getSchemaDirectories() as $directory) {
            $files = glob($directory . '/*.schema.yml');
            if ($files === false) {
                continue;
            }
            foreach ($files as $file) {
                $hashes[$file] = hash_file('sha256', $file);
            }
        }
        ksort($hashes);
        return hash('sha256', serialize($hashes));
    }

    public function keyToResultCache(string $key): string
    {
        return $key;
    }

    public function keyFromResultCache(string $storedKey): string
    {
        return $storedKey;
    }
}
