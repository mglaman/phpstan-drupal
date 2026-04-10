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

    /**
     * @return list<string>
     */
    public function getAllEntityTypeIds(): array
    {
        return array_values(array_keys($this->entityData));
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
