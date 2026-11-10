<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\Analyser\ResultCache\ResultCacheValueExtension;
use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function hash;
use function is_array;
use function is_string;
use function ksort;
use function ltrim;
use function serialize;

/**
 * Re-analyzes the files asking about a service when its definition changes.
 *
 * The key is the service ID. The value covers the service and the services that
 * decide its type: its alias target, parent, and decorators. Only the fields the
 * analysis reads count, so editing a service's arguments or tags re-analyzes
 * nothing.
 */
final class ServiceResultCacheValueExtension implements ResultCacheValueExtension
{

    private const MISSING = 'missing';

    private const RELEVANT_FIELDS = ['class', 'public', 'alias', 'parent', 'deprecated', 'decorates', 'decoration_on_invalid'];

    /** @var array<string, list<string>>|null */
    private ?array $decorators = null;

    public function __construct(
        private readonly DrupalSite $site
    ) {
    }

    public function getValue(string $key): string
    {
        $related = $this->collect($key, []);
        if ($related === []) {
            return self::MISSING;
        }
        ksort($related);
        return hash('sha256', serialize($related));
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
     * @param array<string, array<string, mixed>> $collected
     * @return array<string, array<string, mixed>>
     */
    private function collect(string $id, array $collected): array
    {
        $definitions = $this->site->getServiceDefinitions();
        if (isset($collected[$id]) || !array_key_exists($id, $definitions)) {
            return $collected;
        }
        $definition = $this->normalize($definitions[$id]);
        $collected[$id] = $definition;
        foreach (['alias', 'parent', 'decorates'] as $field) {
            if (isset($definition[$field]) && is_string($definition[$field])) {
                $collected = $this->collect($definition[$field], $collected);
            }
        }
        foreach ($this->getDecorators()[$id] ?? [] as $decorator) {
            $collected = $this->collect($decorator, $collected);
        }
        return $collected;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalize(mixed $definition): array
    {
        // A string is the alias shortcut: "foo: '@bar'".
        if (is_string($definition)) {
            return ['alias' => ltrim($definition, '@')];
        }
        if (!is_array($definition)) {
            return [];
        }
        $definition = array_intersect_key($definition, array_flip(self::RELEVANT_FIELDS));
        ksort($definition);
        return $definition;
    }

    /**
     * @return array<string, list<string>>
     */
    private function getDecorators(): array
    {
        if ($this->decorators === null) {
            $this->decorators = [];
            foreach ($this->site->getServiceDefinitions() as $id => $definition) {
                if (is_array($definition) && isset($definition['decorates']) && is_string($definition['decorates'])) {
                    $this->decorators[$definition['decorates']][] = $id;
                }
            }
        }
        return $this->decorators;
    }
}
