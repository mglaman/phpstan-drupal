<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\ShouldNotHappenException;

class ServiceMap
{
    /** @var \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition[] */
    public static $services = [];

    public function getService(string $id): ?DrupalServiceDefinition
    {
        return self::$services[$id] ?? null;
    }

    /**
     * @return \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition[]
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
                    $serviceDefinition['class'] = $drupalServices[$serviceDefinition['alias']]['class'];
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
