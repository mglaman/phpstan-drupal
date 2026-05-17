<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\DeprecatedScope;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\Deprecations\DeprecatedScopeResolver;
use function strpos;

final class GroupLegacyScope implements DeprecatedScopeResolver
{

    public function isScopeDeprecated(Scope $scope): bool
    {
        if ($scope->isInClass()) {
            $class = $scope->getClassReflection();
            $phpDoc = $class->getResolvedPhpDoc();
            if ($phpDoc !== null && strpos($phpDoc->getPhpDocString(), '@group legacy') !== false) {
                return true;
            }
        }

        $function = $scope->getFunction();
        return $function !== null
            && $function->getDocComment() !== null
            && strpos($function->getDocComment(), '@group legacy') !== false;
    }
}
