<?php

namespace mglaman\PHPStanDrupal\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

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

    public static function canHandleProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        // FieldItemList::__get() delegates to the first item, so any property
        // on the underlying FieldItem is potentially valid.
        return true;
    }

    public function getReadableType(): Type
    {
        if ($this->propertyName === 'entity') {
            return new UnionType([new ObjectType('Drupal\Core\Entity\EntityInterface'), new NullType()]);
        }
        if ($this->propertyName === 'target_id') {
            // @todo needs to be union type.
            return new StringType();
        }
        // @todo this is wrong, integer/bool/decimal/etc all use single value property.
        if ($this->propertyName === 'value') {
            return new StringType();
        }

        // Fallback: unknown properties delegated via __get could be any type.
        return new MixedType();
    }

    public function getWritableType(): Type
    {
        if ($this->propertyName === 'entity') {
            return new UnionType([new ObjectType('Drupal\Core\Entity\EntityInterface'), new NullType()]);
        }
        if ($this->propertyName === 'target_id') {
            return new StringType();
        }
        if ($this->propertyName === 'value') {
            return new StringType();
        }

        // Fallback: unknown properties delegated via __set could be any type.
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

    public function getDocComment(): ?string
    {
        return null;
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
}
