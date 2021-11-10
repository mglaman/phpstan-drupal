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
}
