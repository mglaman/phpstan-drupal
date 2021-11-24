<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

final class EntityDataRepository
{

    /**
     * @var array<string, array<string, string>>
     */
    private $entityData = [];

    public function __construct(array $entityData)
    {
        $this->entityData = $entityData;
    }

    public function get(string $entityTypeId): ?array
    {
        return $this->entityData[$entityTypeId] ?? null;
    }

    public function getClassName(string $entityTypeId): ?string
    {
        $data = $this->get($entityTypeId);
        return $data['class'] ?? null;
    }

    public function getStorageClassName(string $entityTypeId): ?string
    {
        $data = $this->get($entityTypeId);
        return $data['storage'] ?? null;
    }
}
