<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Reflection;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;

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
        // @todo Have this run after PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension
        // We should not have to check for the property tags if we could get this to run after PHPStan's
        // existing annotation property reflection.
        if ($classReflection->hasNativeProperty($propertyName)) {
            // Let other parts of PHPStan handle this.
            return false;
        }

        // A class is its own ancestor, so this also covers the class itself.
        foreach ($classReflection->getAncestors() as $ancestor) {
            if (array_key_exists($propertyName, $ancestor->getPropertyTags())) {
                return false;
            }
        }

        // We need to find a way to parse the entity annotation so that at the minimum the `entity_keys` are
        // supported. The real fix is Drupal developers _really_ need to start writing @property definitions in the
        // class doc if they don't get `get` methods.
        if ($classReflection->is(ContentEntityInterface::class)) {
            // @todo revisit if it's a good idea to be true.
            // Content entities have magical __get... so it is kind of true.
            return true;
        }
        if ($classReflection->is(FieldItemListInterface::class)) {
            return FieldItemListPropertyReflection::canHandleProperty($classReflection, $propertyName);
        }

        return false;
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        if ($classReflection->is(EntityInterface::class)) {
            return new EntityFieldReflection($classReflection, $propertyName);
        }
        if ($classReflection->is(FieldItemListInterface::class)) {
            return new FieldItemListPropertyReflection($classReflection, $propertyName);
        }

        throw new ShouldNotHappenException($classReflection->getName() . "::$propertyName should be handled earlier.");
    }
}
