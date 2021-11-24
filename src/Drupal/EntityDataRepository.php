<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\Reflection\ReflectionProvider;

final class EntityDataRepository
{

    /**
     * @var array<string, array<string, string>>
     */
    private $entityMapping;
    /**
     * @var array<string, EntityData|null>
     */
    private $entityData;

    public function __construct(array $entityMapping)
    {
        $this->entityMapping = $entityMapping;
    }

    public function get(string $entityTypeId): EntityData
    {
        if (!isset($this->entityData[$entityTypeId])) {
            $this->entityData[$entityTypeId] = new EntityData(
                $entityTypeId,
                $this->entityMapping[$entityTypeId] ?? []
            );
        }
        return $this->entityData[$entityTypeId];
    }
}
