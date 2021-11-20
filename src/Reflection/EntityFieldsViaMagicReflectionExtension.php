<?php

namespace mglaman\PHPStanDrupal\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ObjectType;

/**
 * Allows field access via magic methods
 *
 * See \Drupal\Core\Entity\ContentEntityBase::__get and ::__set.
 *
 * @todo split into Entity and FieldItem specifics.
 */
class EntityFieldsViaMagicReflectionExtension implements PropertiesClassReflectionExtension
{

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->hasNativeProperty($propertyName)) {
            // Let other parts of PHPStan handle this.
            return false;
        }

        $reflection = $classReflection->getNativeReflection();
        // We need to find a way to parse the entity annotation so that at the minimum the `entity_keys` are
        // supported. The real fix is Drupal developers _really_ need to start writing @property definitions in the
        // class doc if they don't get `get` methods.
        if ($reflection->implementsInterface('Drupal\Core\Entity\ContentEntityInterface')) {
            // @todo revisit if it's a good idea to be true.
            // Content entities have magical __get... so it is kind of true.
            return true;
        }
        if (self::classObjectIsSuperOfFieldItemList($reflection)->yes()) {
            return FieldItemListPropertyReflection::canHandleProperty($classReflection, $propertyName);
        }

        return false;
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        $reflection = $classReflection->getNativeReflection();
        if ($reflection->implementsInterface('Drupal\Core\Entity\EntityInterface')) {
            return new EntityFieldReflection($classReflection, $propertyName);
        }
        if (self::classObjectIsSuperOfFieldItemList($reflection)->yes()) {
            return new FieldItemListPropertyReflection($classReflection, $propertyName);
        }

        throw new \LogicException($classReflection->getName() . "::$propertyName should be handled earlier.");
    }

    protected static function classObjectIsSuperOfFieldItemList(\ReflectionClass $reflection) : TrinaryLogic
    {
        $classObject = new ObjectType($reflection->getName());
        $interfaceObject = self::getFieldItemListInterfaceObject();
        return $interfaceObject->isSuperTypeOf($classObject);
    }

    protected static function getFieldItemListInterfaceObject() : ObjectType
    {
        return new ObjectType('Drupal\Core\Field\FieldItemListInterface');
    }
}
