<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class EntityDataRepository
{
    /**
     * @var array<string, EntityData>
     */
    private array $entityData = [];

    public function __construct(array $entityMapping)
    {
        foreach ($entityMapping as $entityTypeId => $entityData) {
            $this->entityData[$entityTypeId] = new EntityData(
                $entityTypeId,
                $entityData
            );
        }
    }

    /**
     * @return list<string>
     */
    public function getAllEntityTypeIds(): array
    {
        return array_keys($this->entityData);
    }

    public function get(string $entityTypeId): EntityData
    {
        // Do not store the stub for an unknown ID: getAllEntityTypeIds() would
        // then report it as known, and whether a later file accepts it as an
        // entity-type-id would depend on analysis order.
        return $this->entityData[$entityTypeId] ?? new EntityData($entityTypeId, []);
    }

    public function resolveFromStorage(Type $callerType): ?EntityData
    {
        if ($callerType->equals(new ObjectType(EntityStorageInterface::class))) {
            return null;
        }
        if ($callerType->equals(new ObjectType(ConfigEntityStorageInterface::class))) {
            return null;
        }
        if ($callerType->equals(new ObjectType(ContentEntityStorageInterface::class))) {
            return null;
        }
        foreach ($this->entityData as $entityData) {
            $storageType = $entityData->getStorageType();
            if ($storageType !== null && $callerType->isSuperTypeOf($storageType)->yes()) {
                return $entityData;
            }
        }
        return null;
    }
}
