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
            if ($class->getAttributes(IgnoreDeprecations::class) !== []) {
                return true;
            }

            $function = $scope->getFunction();
            if ($function === null) {
                return false;
            }

            $method = $class->getMethod($function->getName());
            if ($method->getAttributes(IgnoreDeprecations::class) !== []) {
                return true;
            }
        }
        return false;
    }
}
