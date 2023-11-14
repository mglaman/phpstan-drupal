<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Internal;

use PHPStan\Analyser\Scope;

final class DeprecatedScopeCheck
{
    public static function inDeprecatedScope(Scope $scope): bool
    {
        $class = $scope->getClassReflection();
        if ($class !== null && $class->isDeprecated()) {
            return true;
        }
        $trait = $scope->getTraitReflection();
        if ($trait !== null && $trait->isDeprecated()) {
            return true;
        }
        $function = $scope->getFunction();
        return $function !== null && $function->isDeprecated()->yes();
    }
}
