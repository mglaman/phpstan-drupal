<?php

namespace PHPStan\Reflection;

use PHPStan\Type\IntegerType;
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
        // @todo use the class reflection and be more specific about handled properties.
        // Currently \PHPStan\Reflection\EntityFieldReflection::getType always passes FieldItemListInterface.
        $names = ['entity', 'value', 'target_id'];
        return in_array($propertyName, $names, true);
    }

    public function getReadableType(): Type
    {
        if ($this->propertyName === 'entity') {
            return new ObjectType('Drupal\Core\Entity\EntityInterface');
        }
        if ($this->propertyName === 'target_id') {
            return new StringType();
        }
        if ($this->propertyName === 'value') {
            return new StringType();
        }

        // Fallback.
        return new NullType();
    }

    public function getWritableType(): Type
    {
        if ($this->propertyName === 'entity') {
            return new ObjectType('Drupal\Core\Entity\EntityInterface');
        }
        if ($this->propertyName === 'target_id') {
            return new UnionType([new StringType(), new IntegerType()]);
        }
        if ($this->propertyName === 'value') {
            return new StringType();
        }

        // Fallback.
        return new NullType();
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

    public function isDeprecated(): \PHPStan\TrinaryLogic
    {
        return \PHPStan\TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function isInternal(): \PHPStan\TrinaryLogic
    {
        return \PHPStan\TrinaryLogic::createNo();
    }
}
