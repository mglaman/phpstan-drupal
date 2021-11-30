<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\PluginManager;

use ReflectionClass;
use Drupal\Component\Plugin\PluginManagerInterface;
use PHPStan\Rules\Rule;

/**
 * @phpstan-template TNodeType of \PhpParser\Node
 */
abstract class AbstractPluginManagerRule implements Rule
{

    protected function isPluginManager(ReflectionClass $classReflection): bool
    {
        return
            !$classReflection->isInterface() &&
            !$classReflection->isAnonymous() &&
            $classReflection->implementsInterface(PluginManagerInterface::class);
    }
}
