<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\DeprecatedScope;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Deprecations\DeprecatedScopeResolver;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

final class IgnoreDeprecationsScope implements DeprecatedScopeResolver
{

    public function isScopeDeprecated(Scope $scope): bool
    {
        if (!class_exists(IgnoreDeprecations::class)) {
            return false;
        }

        if ($scope->isInClass()) {
            $class = $scope->getClassReflection()->getNativeReflection();
            $classIgnoreDeprecationAttributes = $class->getAttributes(IgnoreDeprecations::class);
            $classIgnoreDeprecationAttribute = $classIgnoreDeprecationAttributes ? $classIgnoreDeprecationAttributes[0] : null;
            if ($classIgnoreDeprecationAttribute && count($classIgnoreDeprecationAttribute->getArguments()) === 0) {
                return true;
            }

            $function = $scope->getFunction();
            if ($function === null) {
                return false;
            }

            $method = $class->getMethod($function->getName());
            $methodIgnoreDeprecationAttributes = $method->getAttributes(IgnoreDeprecations::class);
            $methodIgnoreDeprecationAttribute = $methodIgnoreDeprecationAttributes ? $methodIgnoreDeprecationAttributes[0] : null;
            if ($methodIgnoreDeprecationAttribute && count($methodIgnoreDeprecationAttribute->getArguments()) === 0) {
                return true;
            }
        }
        return false;
    }
}
