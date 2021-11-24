<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\Reflection\ReflectionProvider;

final class EntityDataRepository
{
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    /**
     * @var array<string, array<string, string>>
     */
    private $entityMapping;
    /**
     * @var array<string, EntityData|null>
     */
    private $entityData;

    public function __construct(ReflectionProvider $reflectionProvider, array $entityMapping)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->entityMapping = $entityMapping;
    }

    public function get(string $entityTypeId): EntityData
    {
        if (!isset($this->entityData[$entityTypeId])) {
            $this->entityData[$entityTypeId] = new EntityData(
                $entityTypeId,
                $this->entityMapping[$entityTypeId] ?? [],
                $this->reflectionProvider
            );
        }
        return $this->entityData[$entityTypeId];
    }
}
