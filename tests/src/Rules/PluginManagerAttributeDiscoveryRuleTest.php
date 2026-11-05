<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\PluginManager\PluginManagerAttributeDiscoveryRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;

final class PluginManagerAttributeDiscoveryRuleTest extends DrupalRuleTestCase
{

    private bool $attributeInterfaceAvailable = true;

    protected function getRule(): Rule
    {
        $reflectionProvider = self::createReflectionProvider();
        if ($this->attributeInterfaceAvailable) {
            return new PluginManagerAttributeDiscoveryRule($reflectionProvider);
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
        return new PluginManagerAttributeDiscoveryRule($withoutInterface);
    }

    /**
     * Without AttributeInterface nothing can be told apart, so nothing is reported.
     */
    public function testNoopWithoutAttributeInterface(): void
    {
        $this->attributeInterfaceAvailable = false;
        $this->analyse([__DIR__ . '/data/plugin-manager-attribute-discovery.php'], []);
    }

    public function testRule(): void
    {
        $tip = 'Provide an Attribute class and keep the Annotation class for BC. See https://www.drupal.org/node/3522776 and https://www.drupal.org/node/3395582';
        $message = 'Plugin manager PluginManagerAttributeDiscovery\%s passes Drupal\Component\Annotation\Plugin as the plugin definition attribute name to DefaultPluginManager::__construct(), but it is not an attribute class implementing Drupal\Component\Plugin\Attribute\AttributeInterface. Not supporting attribute discovery is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0.';
        $this->analyse(
            [__DIR__ . '/data/plugin-manager-attribute-discovery.php'],
            [
                [sprintf($message, 'AnnotationOnly'), 15, $tip],
                [sprintf($message, 'AnnotationOnlyAsString'), 25, $tip],
                [sprintf($message, 'AnnotationOnlyNamedArgs'), 39, $tip],
            ]
        );
    }

}
