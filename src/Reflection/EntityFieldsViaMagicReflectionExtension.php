<?php

namespace PHPStan\Reflection;

/**
 * Allows field access via magic methods
 *
 * See \Drupal\Core\Entity\ContentEntityBase::__get and ::__set.
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
        if ($reflection->implementsInterface('Drupal\Core\Field\FieldItemListInterface')) {
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
        if ($reflection->implementsInterface('Drupal\Core\Field\FieldItemListInterface')) {
            return new FieldItemListPropertyReflection($classReflection, $propertyName);
        }

        throw new \LogicException($classReflection->getName() . "::$propertyName should be handled earlier.");
    }
}
