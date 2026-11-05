<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\PluginManager;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Detects plugin managers that do not support attribute discovery.
 *
 * Since drupal:11.2.0 the fifth argument of
 * \Drupal\Core\Plugin\DefaultPluginManager::__construct() is the plugin
 * definition attribute class. Passing anything that is not a subclass of
 * \Drupal\Component\Plugin\Attribute\AttributeInterface (such as an annotation
 * class) falls back to annotation discovery, which is deprecated in
 * drupal:11.2.0 and removed in drupal:12.0.0. Core signals this with a runtime
 * trigger_error() so the deprecation rules cannot detect it.
 *
 * @see https://www.drupal.org/node/3522776
 * @see https://www.drupal.org/node/3395582
 *
 * @implements Rule<StaticCall>
 */
class PluginManagerAttributeDiscoveryRule implements Rule
{
    private const DEFAULT_PLUGIN_MANAGER = 'Drupal\Core\Plugin\DefaultPluginManager';

    private const ATTRIBUTE_INTERFACE = 'Drupal\Component\Plugin\Attribute\AttributeInterface';

    private const ATTRIBUTE_ARGUMENT_NAME = 'plugin_definition_attribute_name';

    private const ATTRIBUTE_ARGUMENT_POSITION = 4;

    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Node\Name || $node->class->toLowerString() !== 'parent') {
            return [];
        }
        if (!$node->name instanceof Node\Identifier || $node->name->toLowerString() !== '__construct') {
            return [];
        }
        if (!$scope->isInClass() || $scope->isInTrait()) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection->isAnonymous() || !$classReflection->is(self::DEFAULT_PLUGIN_MANAGER)) {
            return [];
        }

        // Only the constructor declared by DefaultPluginManager itself has the
        // attribute argument. An intermediate parent may have its own signature.
        $parentClass = $classReflection->getParentClass();
        if ($parentClass === null || !$parentClass->hasConstructor()) {
            return [];
        }
        if ($parentClass->getConstructor()->getDeclaringClass()->getName() !== self::DEFAULT_PLUGIN_MANAGER) {
            return [];
        }

        $argument = $this->findAttributeArgument($node);
        if ($argument === null) {
            // Omitted: core falls back to annotations without a deprecation.
            return [];
        }

        $errors = [];
        foreach ($scope->getType($argument->value)->getConstantStrings() as $constantString) {
            $attributeClassName = ltrim($constantString->getValue(), '\\');
            // Only report when certain: the class must be resolvable.
            if ($attributeClassName === '' || !$this->reflectionProvider->hasClass($attributeClassName)) {
                continue;
            }
            $attributeClass = $this->reflectionProvider->getClass($attributeClassName);
            if ($attributeClass->is(self::ATTRIBUTE_INTERFACE)) {
                continue;
            }
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Plugin manager %s passes %s as the plugin definition attribute name to DefaultPluginManager::__construct(), but it is not an attribute class implementing %s. Not supporting attribute discovery is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0.',
                $classReflection->getName(),
                $attributeClass->getName(),
                self::ATTRIBUTE_INTERFACE
            ))
                ->identifier('pluginManager.attributeDiscoveryMissing')
                ->line($argument->getStartLine())
                ->tip('Provide an Attribute class and keep the Annotation class for BC. See https://www.drupal.org/node/3522776 and https://www.drupal.org/node/3395582')
                ->build();
        }

        return $errors;
    }

    private function findAttributeArgument(StaticCall $node): ?Node\Arg
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
