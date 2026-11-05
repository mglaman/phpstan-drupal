<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\PluginManager;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;

/**
 * Shared helpers for the plugin manager attribute discovery rules.
 *
 * @template TNodeType of \PhpParser\Node
 * @implements Rule<TNodeType>
 */
abstract class AbstractPluginManagerAttributeDiscoveryRule implements Rule
{
    protected const DEFAULT_PLUGIN_MANAGER = 'Drupal\Core\Plugin\DefaultPluginManager';

    protected const ATTRIBUTE_INTERFACE = 'Drupal\Component\Plugin\Attribute\AttributeInterface';

    private const ATTRIBUTE_ARGUMENT_NAME = 'plugin_definition_attribute_name';

    private const ATTRIBUTE_ARGUMENT_POSITION = 4;

    protected ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * Whether the class directly extends DefaultPluginManager's constructor.
     *
     * Only the constructor declared by DefaultPluginManager itself has the
     * attribute argument. An intermediate parent may have its own signature.
     */
    protected function callsDefaultPluginManagerConstructor(ClassReflection $classReflection): bool
    {
        if ($classReflection->isAnonymous() || $classReflection->isTrait() || !$classReflection->is(self::DEFAULT_PLUGIN_MANAGER)) {
            return false;
        }
        $parentClass = $classReflection->getParentClass();
        if ($parentClass === null || !$parentClass->hasConstructor()) {
            return false;
        }
        return $parentClass->getConstructor()->getDeclaringClass()->getName() === self::DEFAULT_PLUGIN_MANAGER;
    }

    protected function isParentConstructorCall(StaticCall $node): bool
    {
        return $node->class instanceof Node\Name
            && $node->class->toLowerString() === 'parent'
            && $node->name instanceof Node\Identifier
            && $node->name->toLowerString() === '__construct';
    }

    /**
     * Returns the class when it resolves and is not an attribute class.
     */
    protected function resolveNonAttributeClass(string $className): ?ClassReflection
    {
        // Drupal < 10.2 has no attribute interface. Without it nothing can be
        // told apart from an attribute class, so never report.
        if (!$this->reflectionProvider->hasClass(self::ATTRIBUTE_INTERFACE)) {
            return null;
        }
        $className = ltrim($className, '\\');
        // Only report when certain: the class must be resolvable.
        if ($className === '' || !$this->reflectionProvider->hasClass($className)) {
            return null;
        }
        $class = $this->reflectionProvider->getClass($className);
        return $class->is(self::ATTRIBUTE_INTERFACE) ? null : $class;
    }

    protected function findAttributeArgument(StaticCall $node): ?Node\Arg
    {
        $position = 0;
        foreach ($node->getArgs() as $arg) {
            if ($arg->unpack) {
                return null;
            }
            if ($arg->name !== null) {
                if ($arg->name->toString() === self::ATTRIBUTE_ARGUMENT_NAME) {
                    return $arg;
                }
                continue;
            }
            if ($position === self::ATTRIBUTE_ARGUMENT_POSITION) {
                return $arg;
            }
            $position++;
        }
        return null;
    }
}
