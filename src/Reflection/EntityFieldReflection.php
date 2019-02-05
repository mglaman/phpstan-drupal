<?php

namespace PHPStan\Reflection;

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

    public function getType(): Type
    {
        if ($this->propertyName == 'original') {
          // See Drupal\Core\Entity\EntityStorageBase::doPreSave
            return new ObjectType('Drupal\Core\Field\FieldItemListInterface');
        }

        return new ObjectType('Drupal\Core\Field\FieldItemListInterface');
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
}
