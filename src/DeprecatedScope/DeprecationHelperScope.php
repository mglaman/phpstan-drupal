<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\DeprecatedScope;

use Drupal\Component\Utility\DeprecationHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Deprecations\DeprecatedScopeResolver;
use function class_exists;
use function count;

final class DeprecationHelperScope implements DeprecatedScopeResolver
{
    public function isScopeDeprecated(Scope $scope): bool
    {
        if (!class_exists(DeprecationHelper::class)) {
            return false;
        }
        $callStack = $scope->getFunctionCallStackWithParameters();
        if (count($callStack) === 0) {
            return false;
        }
        [$function, $parameter] = $callStack[0];
        if (!$function instanceof MethodReflection) {
            return false;
        }
        if ($function->getName() !== 'backwardsCompatibleCall'
            || $function->getDeclaringClass()->getName() !== DeprecationHelper::class
        ) {
            return false;
        }
        return $parameter !== null && $parameter->getName() === 'deprecatedCallable';
    }
}
