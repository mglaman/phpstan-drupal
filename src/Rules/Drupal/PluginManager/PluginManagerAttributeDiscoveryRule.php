<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\PluginManager;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
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
 * This rule handles values PHPStan can type as a constant string. See
 * PluginManagerAttributeDiscoveryDefaultRule for defaults of static properties
 * and constructor parameters.
 *
 * @see https://www.drupal.org/node/3522776
 * @see https://www.drupal.org/node/3395582
 *
 * @extends AbstractPluginManagerAttributeDiscoveryRule<StaticCall>
 */
final class PluginManagerAttributeDiscoveryRule extends AbstractPluginManagerAttributeDiscoveryRule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isParentConstructorCall($node)) {
            return [];
        }
        if (!$scope->isInClass() || !$this->callsDefaultPluginManagerConstructor($scope->getClassReflection())) {
            return [];
        }
        $classReflection = $scope->getClassReflection();

        $argument = $this->findAttributeArgument($node);
        if ($argument === null) {
            // Omitted: core falls back to annotations without a deprecation.
            return [];
        }

        $errors = [];
        foreach ($scope->getType($argument->value)->getConstantStrings() as $constantString) {
            $attributeClass = $this->resolveNonAttributeClass($constantString->getValue());
            if ($attributeClass === null) {
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
}
