<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\PluginManager;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;

/**
 * @template TNodeType of \PhpParser\Node
 * @implements Rule<TNodeType>
 */
abstract class AbstractPluginManagerRule implements Rule
{

    protected function isPluginManager(ClassReflection $classReflection): bool
    {
        return
            !$classReflection->isInterface() &&
            !$classReflection->isAnonymous() &&
            $classReflection->implementsInterface('Drupal\Component\Plugin\PluginManagerInterface');
    }
}
