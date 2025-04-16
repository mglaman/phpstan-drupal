<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use function class_exists;

class ServiceMap
{
    /** @var DrupalServiceDefinition[] */
    private static $services = [];

    public function getService(string $id): ?DrupalServiceDefinition
    {
        return self::$services[$id] ?? null;
    }

    /**
     * @return DrupalServiceDefinition[]
     */
    public function getServices(): array
    {
        return self::$services;
    }

    public function setDrupalServices(array $drupalServices): void
    {
        self::$services = [];
        $decorators = [];

        foreach ($drupalServices as $serviceId => $serviceDefinition) {
            // @todo this duplicates \Symfony\Component\DependencyInjection\Loader\YamlFileLoader::parseDefinition()
            //   Can we re-use the YamlFileLoader instead of this manual parsing?
            if (is_string($serviceDefinition) && strpos($serviceDefinition, '@') === 0) {
                $serviceDefinition = [
                    'alias' => substr($serviceDefinition, 1)
                ];
            }
            if (is_null($serviceDefinition)) {
                $serviceDefinition = [];
            }

            if (!is_array($serviceDefinition)) {
                continue;
            }

            if (isset($serviceDefinition['alias'], $drupalServices[$serviceDefinition['alias']])) {
                $serviceDefinition = $drupalServices[$serviceDefinition['alias']];
            }
            if (isset($serviceDefinition['parent'], $drupalServices[$serviceDefinition['parent']])) {
                $serviceDefinition = $this->resolveParentDefinition($serviceDefinition['parent'], $serviceDefinition, $drupalServices);
            }

            if (isset($serviceDefinition['decorates'])) {
                $decorators[$serviceDefinition['decorates']][] = $serviceId;
            }

            // @todo support factories
            if (isset($serviceDefinition['factory'])) {
                continue;
            }

            if (!isset($serviceDefinition['class'])) {
                if (class_exists($serviceId)) {
                    $serviceDefinition['class'] = $serviceId;
                } else {
                    continue;
                }
            }
            self::$services[$serviceId] = new DrupalServiceDefinition(
                (string) $serviceId,
                $serviceDefinition['class'],
                $serviceDefinition['public'] ?? true,
                $serviceDefinition['alias'] ?? null
            );
            $deprecated = $serviceDefinition['deprecated'] ?? null;
            if ($deprecated) {
                if (is_array($deprecated) && isset($deprecated['message'])) {
                    $deprecated = $deprecated['message'];
                }
                $deprecated = str_replace('%service_id%', $serviceId, $deprecated);
                if (isset($serviceDefinition['alias'])) {
                    $deprecated = str_replace('%alias_id%', $serviceDefinition['alias'], $deprecated);
                }
                self::$services[$serviceId]->setDeprecated(true, $deprecated);
            }
        }

        foreach ($decorators as $decorated_service_id => $services) {
            if (!isset(self::$services[$decorated_service_id])) {
                continue;
            }
            foreach ($services as $decorating_service_id) {
                if (!isset(self::$services[$decorating_service_id])) {
                    continue;
                }
                self::$services[$decorated_service_id]->addDecorator(self::$services[$decorating_service_id]);
            }
        }
    }

    private function resolveParentDefinition(string $parentId, array $serviceDefinition, array $drupalServices): array
    {
        $parentDefinition = $drupalServices[$parentId] ?? [];
        if ([] === $parentDefinition) {
            return $parentDefinition;
        }

        if (isset($parentDefinition['parent'])) {
            if (!isset($drupalServices[$parentDefinition['parent']])) {
                return $serviceDefinition;
            }

            $parentDefinition = $this->resolveParentDefinition($parentDefinition['parent'], $drupalServices[$parentDefinition['parent']], $drupalServices);
        }

        if (isset($parentDefinition['class']) && !isset($serviceDefinition['class'])) {
            $serviceDefinition['class'] = $parentDefinition['class'];
        }

        if (isset($parentDefinition['public']) && !isset($serviceDefinition['public'])) {
            $serviceDefinition['public'] = $parentDefinition['public'];
        }

        return $serviceDefinition;
    }
}
