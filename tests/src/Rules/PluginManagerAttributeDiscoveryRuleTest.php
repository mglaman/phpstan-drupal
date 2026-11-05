<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\PluginManager\PluginManagerAttributeDiscoveryRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class PluginManagerAttributeDiscoveryRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new PluginManagerAttributeDiscoveryRule(
            self::createReflectionProvider()
        );
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
