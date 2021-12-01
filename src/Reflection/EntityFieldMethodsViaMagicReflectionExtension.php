<?php

namespace mglaman\PHPStanDrupal\Reflection;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MethodReflection;

/**
 * Allows some common methods on fields.
 */
class EntityFieldMethodsViaMagicReflectionExtension implements MethodsClassReflectionExtension
{

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        $reflection = $classReflection->getNativeReflection();
        if (EntityFieldsViaMagicReflectionExtension::classObjectIsSuperOfFieldItemList($reflection)) {
            return FieldItemListMethodReflection::canHandleMethod($classReflection, $methodName);
        }
        return false;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new FieldItemListMethodReflection($methodName);
    }

}
