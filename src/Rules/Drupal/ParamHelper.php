<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

final class ParamHelper
{

    public static function isValidParam(Node\Param $param, string $expectedFqcn, bool $isNullable, Scope $scope): bool
    {
        if ($param->type === null) {
            return false;
        }

        $type = $param->type;
        $paramIsNullable = $type instanceof Node\NullableType;
        if ($paramIsNullable) {
            $type = $type->type;
        }

        if (!($type instanceof Node\Name) || $scope->resolveName($type) !== $expectedFqcn) {
            return false;
        }

        if (!$paramIsNullable && $param->default !== null) {
            $paramIsNullable = $scope->getType($param->default)->isNull()->yes();
        }

        return !$isNullable || $paramIsNullable;
    }
}
