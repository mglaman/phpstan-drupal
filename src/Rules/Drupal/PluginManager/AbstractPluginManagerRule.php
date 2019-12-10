<?php declare(strict_types=1);

namespace PHPStan\Rules\Drupal\PluginManager;

use PHPStan\Rules\Rule;

/**
 * @phpstan-template TNodeType of \PhpParser\Node
 */
abstract class AbstractPluginManagerRule implements Rule
{

    protected function isPluginManager(\ReflectionClass $classReflection): bool
    {
        return
            !$classReflection->isInterface() &&
            !$classReflection->isAnonymous() &&
            $classReflection->implementsInterface('Drupal\Component\Plugin\PluginManagerInterface');
    }
}
