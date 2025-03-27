<?php

namespace mglaman\PHPStanDrupal\Reflection;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Allows field access via magic methods
 *
 * See \Drupal\Core\Entity\ContentEntityBase::__get and ::__set.
 */
class EntityFieldReflection implements PropertyReflection
{

  /** @var ClassReflection */
    private $declaringClass;

  /** @var string */
    private $propertyName;

    private ReflectionProvider $reflectionProvider;

    public function __construct(ClassReflection $declaringClass, string $propertyName, ReflectionProvider $reflectionProvider)
    {
        $this->declaringClass = $declaringClass;
        $this->propertyName = $propertyName;
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getReadableType(): Type
    {
        if ($this->propertyName === 'original') {
            if ($this->isContentEntityType()) {
                $objectType = ContentEntityInterface::class;
            } elseif ($this->isConfigEntityType()) {
                $objectType = ConfigEntityInterface::class;
            } else {
                $objectType = EntityInterface::class;
            }
            return new ObjectType($objectType);
        }

        if ($this->isContentEntityType()) {
            // Assume the property is a field.
            return new ObjectType(FieldItemListInterface::class);
        }

        return new MixedType();
    }

    private function isContentEntityType(): bool
    {
        return $this->declaringClass->isSubclassOfClass($this->reflectionProvider->getClass(ContentEntityInterface::class));
    }

    private function isConfigEntityType(): bool
    {
        return $this->declaringClass->isSubclassOfClass($this->reflectionProvider->getClass(ConfigEntityInterface::class));
    }

    public function getWritableType(): Type
    {
        if ($this->propertyName === 'original') {
            if ($this->isContentEntityType()) {
                $objectType = ContentEntityInterface::class;
            } elseif ($this->isConfigEntityType()) {
                $objectType = ConfigEntityInterface::class;
            } else {
                $objectType = EntityInterface::class;
            }
            return new ObjectType($objectType);
        }

        // @todo Drupal allows $entity->field_myfield = 'string'; does this break that?
        if ($this->isContentEntityType()) {
            // Assume the property is a field.
            return new ObjectType(FieldItemListInterface::class);
        }

        return new MixedType();
    }

    public function canChangeTypeAfterAssignment(): bool
    {
        return true;
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function getDocComment(): ?string
    {
        return null;
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
}
