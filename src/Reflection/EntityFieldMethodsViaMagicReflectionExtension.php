<?php

namespace mglaman\PHPStanDrupal\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ObjectType;

/**
 * Allows some common methods on fields.
 */
class EntityFieldMethodsViaMagicReflectionExtension implements MethodsClassReflectionExtension
{

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->hasNativeMethod($methodName)) {
            // Let other parts of PHPStan handle this.
            return false;
        }
        $reflection = $classReflection->getNativeReflection();
        $interfaceObject = new ObjectType('Drupal\Core\Field\EntityReferenceFieldItemListInterface');
        if (EntityFieldsViaMagicReflectionExtension::classObjectIsSuperOfInterface($reflection, $interfaceObject)->yes()) {
            return FieldItemListMethodReflection::canHandleMethod($classReflection, $methodName);
        }
        return false;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new FieldItemListMethodReflection($methodName);
    }
}
