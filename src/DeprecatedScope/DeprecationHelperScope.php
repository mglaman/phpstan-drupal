<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\DeprecatedScope;

use Drupal\Component\Utility\DeprecationHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Deprecations\DeprecatedScopeResolver;
use function class_exists;

final class DeprecationHelperScope implements DeprecatedScopeResolver
{
    public function isScopeDeprecated(Scope $scope): bool
    {
        if (!class_exists(DeprecationHelper::class)) {
            return false;
        }
        foreach ($scope->getFunctionCallStackWithParameters() as [$function, $parameter]) {
            if (!$function instanceof MethodReflection) {
                continue;
            }
            if ($function->getName() !== 'backwardsCompatibleCall'
                || $function->getDeclaringClass()->getName() !== DeprecationHelper::class
            ) {
                continue;
            }
            if ($parameter !== null && $parameter->getName() === 'deprecatedCallable') {
                return true;
            }
        }
        return false;
    }
}
