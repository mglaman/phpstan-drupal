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
                continue;
            }
            $this->services[$serviceId] = new DrupalServiceDefinition(
                $serviceId,
                $serviceDefinition['class'],
                $serviceDefinition['public'] ?? true,
                $serviceDefinition['alias'] ?? null
            );
        }
    }

    /**
     * @return DrupalServiceDefinition[]
     */
    public function getServices(): array
    {
        return $this->services;
    }

    public function getService(string $id): ?DrupalServiceDefinition
    {
        return $this->services[$id] ?? null;
    }
}
