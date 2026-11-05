<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\PluginManager\PluginManagerAttributeDiscoveryDefaultRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;

final class PluginManagerAttributeDiscoveryDefaultRuleTest extends DrupalRuleTestCase
{

    private bool $attributeInterfaceAvailable = true;

    protected function getRule(): Rule
    {
        $reflectionProvider = self::createReflectionProvider();
        if ($this->attributeInterfaceAvailable) {
            return new PluginManagerAttributeDiscoveryDefaultRule($reflectionProvider);
        }

        // Simulate Drupal < 10.2, where AttributeInterface does not exist.
        $withoutInterface = $this->createMock(ReflectionProvider::class);
        $withoutInterface->method('hasClass')->willReturnCallback(
            static fn (string $name): bool => ltrim($name, '\\') !== 'Drupal\Component\Plugin\Attribute\AttributeInterface'
                && $reflectionProvider->hasClass($name)
        );
        $withoutInterface->method('getClass')->willReturnCallback(
            static fn (string $name) => $reflectionProvider->getClass($name)
        );
        return new PluginManagerAttributeDiscoveryDefaultRule($withoutInterface);
    }

    /**
     * Without AttributeInterface nothing can be told apart, so nothing is reported.
     */
    public function testNoopWithoutAttributeInterface(): void
    {
        $this->attributeInterfaceAvailable = false;
        $this->analyse([__DIR__ . '/data/plugin-manager-attribute-discovery-default.php'], []);
    }

    public function testRule(): void
    {
        $tip = 'Provide an Attribute class and keep the Annotation class for BC. See https://www.drupal.org/node/3522776 and https://www.drupal.org/node/3395582';
        $property = 'Plugin manager PluginManagerAttributeDiscoveryDefault\%s passes the value of static property $annotation, which defaults to Drupal\Component\Annotation\Plugin, as the plugin definition attribute name to DefaultPluginManager::__construct(), but that is not an attribute class implementing Drupal\Component\Plugin\Attribute\AttributeInterface. Unless a subclass overrides the property with an attribute class, not supporting attribute discovery is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0.';
        $parameter = 'Plugin manager PluginManagerAttributeDiscoveryDefault\%s forwards constructor parameter $%s, which defaults to Drupal\Component\Annotation\Plugin, as the plugin definition attribute name to DefaultPluginManager::__construct(), but that is not an attribute class implementing Drupal\Component\Plugin\Attribute\AttributeInterface. Unless callers pass an attribute class, not supporting attribute discovery is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0.';
        $this->analyse(
            [__DIR__ . '/data/plugin-manager-attribute-discovery-default.php'],
            [
                [sprintf($property, 'StaticPropertyAnnotation'), 18, $tip],
                [sprintf($property, 'SelfPropertyAnnotation'), 31, $tip],
                [sprintf($property, 'RedeclaredToAnnotation'), 51, $tip],
                [sprintf($parameter, 'ParameterDefaultAnnotation', 'plugin_definition_annotation_name'), 128, $tip],
                [sprintf($parameter, 'ParameterDefaultClassConstNamed', 'annotation'), 139, $tip],
                [sprintf($property, 'AnonymousClassBeforeConstructorReports'), 223, $tip],
                [sprintf($parameter, 'NestedScopesInConstructorReports', 'annotation'), 296, $tip],
                [sprintf($parameter, 'ParameterShadowedInNestedScopes', 'annotation'), 358, $tip],
            ]
        );
    }

}
