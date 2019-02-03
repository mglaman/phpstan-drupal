<?php declare(strict_types=1);

namespace PHPStan\Drupal;

class ServiceMap
{
    /** @var \PHPStan\Drupal\DrupalServiceDefinition[] */
    private $services;

    /**
     * ServiceMap constructor.
     * @param array $drupalServices
     */
    public function __construct(array $drupalServices)
    {
        $this->services = [];

        foreach ($drupalServices as $serviceId => $serviceDefinition) {
            // @todo support factories
            if (!isset($serviceDefinition['class'])) {
                if (isset($serviceDefinition['alias'], $drupalServices[$serviceDefinition['alias']])) {
                    $serviceDefinition['class'] = $drupalServices[$serviceDefinition['alias']]['class'];
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
        }
    }

    public function getService(string $id): ?DrupalServiceDefinition
    {
        return $this->services[$id] ?? null;
    }
}
