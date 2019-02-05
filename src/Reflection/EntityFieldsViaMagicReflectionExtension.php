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
        if ($reflection->implementsInterface('Drupal\Core\Entity\EntityInterface')) {
            return true;
        }
        if ($reflection->implementsInterface('Drupal\Core\Field\FieldItemListInterface')) {
            return FieldItemListPropertyReflection::canHandleProperty($propertyName);
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
