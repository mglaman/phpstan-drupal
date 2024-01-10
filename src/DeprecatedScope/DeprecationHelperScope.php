<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\DeprecatedScope;

use Drupal\Component\Utility\DeprecationHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Rules\Deprecations\DeprecatedScopeResolver;

final class DeprecationHelperScope implements DeprecatedScopeResolver
{
    public function isScopeDeprecated(Scope $scope): bool
    {
        $callStack = $scope->getFunctionCallStack();
        if (count($callStack) === 0) {
            return false;
        }
        $previousCall = $callStack[0];
        if (!$previousCall instanceof PhpMethodReflection) {
            return false;
        }
        // Only run on \Drupal\Component\Utility\DeprecationHelper::backwardsCompatibleCall().
        if ($previousCall->getName() !== 'backwardsCompatibleCall'
            || $previousCall->getDeclaringClass()->getName() !== DeprecationHelper::class
        ) {
            return false;
        }
        // @todo this currently marks `$currentCallable` as a deprecated scope.
        return true;
    }
}
