<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;
use PHPStan\Type\VerbosityLevel;

/**
 * Re-analyzes the files asking about a container parameter when its type changes.
 *
 * The key is the parameter name. The value is the parameter's type, so changing
 * a default without changing its kind re-analyzes nothing.
 */
final class ContainerParameterResultCacheValueExtension implements ResultCacheValueExtension
{

    private const UNKNOWN = 'unknown';

    public function __construct(
        private readonly ContainerParameterMap $parameterMap
    ) {
    }

    public function getValue(string $key): string
    {
        return $this->parameterMap->getType($key)?->describe(VerbosityLevel::cache()) ?? self::UNKNOWN;
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
