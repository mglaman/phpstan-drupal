<?php

namespace mglaman\PHPStanDrupal\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
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

    public function __construct(ClassReflection $declaringClass, string $propertyName)
    {
        $this->declaringClass = $declaringClass;
        $this->propertyName = $propertyName;
    }

    public function getReadableType(): Type
    {
        if ($this->propertyName === 'original') {
            if ($this->declaringClass->isSubclassOf('Drupal\Core\Entity\ContentEntityInterface')) {
                $objectType = 'Drupal\Core\Entity\ContentEntityInterface';
            } elseif ($this->declaringClass->isSubclassOf('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
                $objectType = 'Drupal\Core\Config\Entity\ConfigEntityInterface';
            } else {
                $objectType = 'Drupal\Core\Entity\EntityInterface';
            }
            return new ObjectType($objectType);
        }

        if ($this->declaringClass->isSubclassOf('Drupal\Core\Entity\ContentEntityInterface')) {
            // Assume the property is a field.
            return new ObjectType('Drupal\Core\Field\FieldItemListInterface');
        }

        return new MixedType();
    }

    public function getWritableType(): Type
    {
        if ($this->propertyName === 'original') {
            if ($this->declaringClass->isSubclassOf('Drupal\Core\Entity\ContentEntityInterface')) {
                $objectType = 'Drupal\Core\Entity\ContentEntityInterface';
            } elseif ($this->declaringClass->isSubclassOf('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
                $objectType = 'Drupal\Core\Config\Entity\ConfigEntityInterface';
            } else {
                $objectType = 'Drupal\Core\Entity\EntityInterface';
            }
            return new ObjectType($objectType);
        }

        // @todo Drupal allows $entity->field_myfield = 'string'; does this break that?
        if ($this->declaringClass->isSubclassOf('Drupal\Core\Entity\ContentEntityInterface')) {
            // Assume the property is a field.
            return new ObjectType('Drupal\Core\Field\FieldItemListInterface');
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
