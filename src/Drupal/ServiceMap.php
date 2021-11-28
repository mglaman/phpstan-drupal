<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

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

        foreach ($drupalServices as $serviceId => $serviceDefinition) {
            if (isset($serviceDefinition['parent'], $drupalServices[$serviceDefinition['parent']])) {
                $serviceDefinition = $this->resolveParentDefinition($serviceDefinition['parent'], $serviceDefinition, $drupalServices);
            }

            // @todo support factories
            if (!isset($serviceDefinition['class'])) {
                if (isset($serviceDefinition['alias'], $drupalServices[$serviceDefinition['alias']])) {
                    $aliasedService = $drupalServices[$serviceDefinition['alias']];

                    if (isset($aliasedService['class'])) {
                        $serviceDefinition['class'] = $drupalServices[$serviceDefinition['alias']]['class'];
                    } elseif (class_exists($serviceDefinition['alias'])) {
                        $serviceDefinition['class'] = $serviceDefinition['alias'];
                    }
                } elseif (class_exists($serviceId)) {
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
                self::$services[$serviceId]->setDeprecated(true, $deprecated);
            }
        }
    }

    private function resolveParentDefinition(string $parentId, array $serviceDefinition, array $drupalServices): array
    {
        $parentDefinition = $drupalServices[$parentId] ?? [];
        if ([] === $parentDefinition) {
            return $serviceDefinition;
        }

        if (isset($parentDefinition['parent'])) {
            if (!isset($drupalServices[$parentDefinition['parent']])) {
                return $serviceDefinition;
            }

            return $this->resolveParentDefinition($parentDefinition['parent'], $drupalServices[$parentDefinition['parent']], $drupalServices);
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
