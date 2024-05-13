<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Internal;

use PhpParser\Node\Stmt\Class_;

/**
 * @internal
 */
final class NamespaceCheck
{
    public static function isDrupalNamespace(Class_ $class): bool
    {
        if (!isset($class->namespacedName)) {
            return false;
        }

        return 'Drupal' === (string) $class->namespacedName->slice(0, 1);
    }

    public static function isSharedNamespace(Class_ $class): bool
    {
        if (!isset($class->extends)) {
            return false;
        }

        if (!isset($class->namespacedName)) {
            return false;
        }

        if (!self::isDrupalNamespace($class)) {
            return false;
        }

        return (string) $class->namespacedName->slice(0, 2) === (string) $class->extends->slice(0, 2);
    }
}
