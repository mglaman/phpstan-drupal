<?php

namespace mglaman\PHPStanDrupal\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\ObjectType;
use function array_key_exists;

/**
 * Allows some common methods on fields.
 */
class EntityFieldMethodsViaMagicReflectionExtension implements MethodsClassReflectionExtension
{

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->hasNativeMethod($methodName) || array_key_exists($methodName, $classReflection->getMethodTags())) {
            // Let other parts of PHPStan handle this.
            return false;
        }
        $interfaceObject = new ObjectType('Drupal\Core\Field\FieldItemListInterface');
        $objectType = new ObjectType($classReflection->getName());
        if (!$interfaceObject->isSuperTypeOf($objectType)->yes()) {
            return false;
        }

        if ($methodName === 'referencedEntities') {
            return true;
        }

        return false;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        if ($methodName === 'referencedEntities') {
            $entityReferenceFieldItemListInterfaceType = new ObjectType('Drupal\Core\Field\EntityReferenceFieldItemListInterface');
            $classReflection = $entityReferenceFieldItemListInterfaceType->getClassReflection();
            assert($classReflection !== null);
        }

        return new FieldItemListMethodReflection(
            $classReflection,
            $methodName
        );
    }
}
