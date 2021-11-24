<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use mglaman\PHPStanDrupal\Type\EntityStorage\ConfigEntityStorageType;
use mglaman\PHPStanDrupal\Type\EntityStorage\ContentEntityStorageType;
use mglaman\PHPStanDrupal\Type\EntityStorage\EntityStorageType;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;

final class EntityDataRepository
{
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    /**
     * @var array<string, array<string, string>>
     */
    private $entityData;

    public function __construct(ReflectionProvider $reflectionProvider, array $entityData)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->entityData = $entityData;
    }

    public function get(string $entityTypeId): ?array
    {
        return $this->entityData[$entityTypeId] ?? null;
    }

    public function getClassType(string $entityTypeId): ?ObjectType
    {
        $data = $this->get($entityTypeId);
        $className = $data['class'] ?? null;
        if ($className === null) {
            return null;
        }
        return new ObjectType($className);
    }

    public function getStorageType(string $entityTypeId): ?ObjectType
    {
        $data = $this->get($entityTypeId);
        $className = $data['storage'] ?? null;
        if ($className === null) {
            // @todo get entity type class reflection and return proper storage for entity type
            // example: config storage, sqlcontententitystorage, etc.
            // $className = reflectedDecision.
            return null;
        }
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $reflection = $this->reflectionProvider->getClass($className);
        if ($reflection->implementsInterface(ConfigEntityStorageInterface::class)) {
            return new ConfigEntityStorageType($entityTypeId, $className);
        }
        if ($reflection->implementsInterface(ContentEntityStorageInterface::class)) {
            return new ContentEntityStorageType($entityTypeId, $className);
        }
        return new EntityStorageType($entityTypeId, $className);
    }
}
