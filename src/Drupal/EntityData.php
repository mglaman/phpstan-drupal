<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use mglaman\PHPStanDrupal\Type\EntityStorage\ConfigEntityStorageType;
use mglaman\PHPStanDrupal\Type\EntityStorage\ContentEntityStorageType;
use mglaman\PHPStanDrupal\Type\EntityStorage\EntityStorageType;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Type\ObjectType;

final class EntityData
{

    /**
     * @var string
     */
    private $entityTypeId;

    /**
     * @var string|null
     */
    private $className;

    /**
     * @var string|null
     */
    private $storageClassName;

    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    public function __construct(string $entityTypeId, array $definition, ReflectionProvider $reflectionProvider)
    {
        $this->entityTypeId = $entityTypeId;
        $this->className = $definition['class'] ?? null;
        $this->storageClassName = $definition['storage'] ?? null;
        // \PHPStan\Reflection\ReflectionProviderStaticAccessor::getInstance is not covered by the backward
        // compatibility promise of PHPStan, so we must add it to our value object.
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getClassType(): ?ObjectType
    {
        return $this->className === null ? null : new ObjectType($this->className);
    }

    public function getStorageType(): ?ObjectType
    {
        if ($this->storageClassName === null) {
            // @todo get entity type class reflection and return proper storage for entity type
            // example: config storage, sqlcontententitystorage, etc.
            // $className = reflectedDecision.
            return null;
        }
        if (!$this->reflectionProvider->hasClass($this->storageClassName)) {
            return null;
        }

        // @todo drop reflectionProvider for ObjectType isSuperTypeOf
        $reflection = $this->reflectionProvider->getClass($this->storageClassName);
        if ($reflection->implementsInterface(ConfigEntityStorageInterface::class)) {
            return new ConfigEntityStorageType($this->entityTypeId, $this->storageClassName);
        }
        if ($reflection->implementsInterface(ContentEntityStorageInterface::class)) {
            return new ContentEntityStorageType($this->entityTypeId, $this->storageClassName);
        }
        return new EntityStorageType($this->entityTypeId, $this->storageClassName);
    }
}
