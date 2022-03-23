<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\Type\ObjectType;

final class EntityDataRepository
{
    /**
     * @var array<string, EntityData>
     */
    private $entityData;

    public function __construct(array $entityMapping)
    {
        foreach ($entityMapping as $entityTypeId => $entityData) {
            $this->entityData[$entityTypeId] = new EntityData(
                $entityTypeId,
                $entityData
            );
        }
    }

    public function get(string $entityTypeId): EntityData
    {
        if (!isset($this->entityData[$entityTypeId])) {
            $this->entityData[$entityTypeId] = new EntityData(
                $entityTypeId,
                []
            );
        }
        return $this->entityData[$entityTypeId];
    }

    public function resolveFromStorage(ObjectType $callerType): ?EntityData
    {
        foreach ($this->entityData as $entityData) {
            $storageType = $entityData->getStorageType();
            if ($storageType !== null && $callerType->isSuperTypeOf($storageType)->yes()) {
                return $entityData;
            }
        }
        return null;
    }
}
