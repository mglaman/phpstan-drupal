<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\RestrictedUsage\RestrictedPropertyUsageRule;
use PHPStan\Rules\Rule;

final class EntityOriginalPropertyTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        /** @phpstan-ignore phpstanApi.constructor */
        return self::getContainer()->getByType(RestrictedPropertyUsageRule::class);
    }

    public function testOriginalProperty(): void
    {
        $description = 'in drupal:11.2.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Entity\EntityInterface::getOriginal() instead. See https://www.drupal.org/node/3295826';
        $interface = 'Access to deprecated property $original of interface Drupal\Core\Entity\ContentEntityInterface:' . "\n" . $description;
        $class = 'Access to deprecated property $original of class Drupal\entity_test\Entity\EntityTest:' . "\n" . $description;
        $config = 'Access to deprecated property $original of class Drupal\module_installer_config_test\Entity\TestConfigType:' . "\n" . $description;
        $errors = [
            [$interface, 12],
            [$interface, 13],
            [$interface, 14],
            [$interface, 16],
            [$class, 17],
            [$config, 18],
        ];
        // The property is only deprecated since Drupal 11.2.
        if (version_compare(\Drupal::VERSION, '11.2', '<')) {
            $errors = [];
        }
        $this->analyse([__DIR__ . '/data/entity-original-property.php'], $errors);
    }
}
