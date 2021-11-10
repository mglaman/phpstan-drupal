<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\ShouldNotHappenException;

class ServiceMap
{
    /** @var \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition[] */
    private $services = [];

    public function getService(string $id): ?DrupalServiceDefinition
    {
        // @see notes in DrupalAutoloader.
        // This is all a work around due to inability to set container parameters.
        if (count($this->services) === 0) {
            $this->services = $GLOBALS['drupalServiceMap'] ?? [];
            if (count($this->services) === 0) {
                throw new ShouldNotHappenException('No Drupal service map was registered.');
            }
        }
        return $this->services[$id] ?? null;
    }

    /**
     * @return \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition[]
     */
    public function getServices(): array
    {
        return $this->services;
    }

    public function setDrupalServices(array $drupalServices): void
    {
        $this->services = [];

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
            $this->services[$serviceId] = new DrupalServiceDefinition(
                (string) $serviceId,
                $serviceDefinition['class'],
                $serviceDefinition['public'] ?? true,
                $serviceDefinition['alias'] ?? null
            );
            $deprecated = $serviceDefinition['deprecated'] ?? null;
            if ($deprecated) {
                $this->services[$serviceId]->setDeprecated(true, $deprecated);
            }
        }
    }
}
