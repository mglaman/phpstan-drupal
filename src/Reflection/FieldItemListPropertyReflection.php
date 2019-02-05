<?php

namespace PHPStan\Reflection;

use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Allows field access via magic methods
 *
 * See \Drupal\Core\Field\FieldItemListInterface::__get and ::__set.
 */
class FieldItemListPropertyReflection implements PropertyReflection
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

    public static function canHandleProperty(string $propertyName): bool
    {
        $names = ['entity', 'value', 'target_id'];
        return in_array($propertyName, $names, true);
    }

    public function getType(): Type
    {
        if ($this->propertyName == 'entity') {
          // It was a EntityReferenceFieldItemList
            return new ObjectType('Drupal\Core\Entity\FieldableEntityInterface');
        }
        if ($this->propertyName == 'target_id') {
          // It was a EntityReferenceFieldItemList
            return new IntegerType();
        }
        if ($this->propertyName == 'value') {
            return new MixedType();
        }
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
