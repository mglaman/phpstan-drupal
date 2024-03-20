<?php

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\DependencySerializationTraitPropertyRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class DependencySerializationTraitPropertyRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new DependencySerializationTraitPropertyRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__.'/data/dependency-serialization-trait.php'],
            [
                [
                    'Private properties cannot be serialized with Drupal\Core\DependencyInjection\DependencySerializationTrait.',
                    16,
                    'See https://www.drupal.org/node/3110266',
                ],
                [
                    'Private properties cannot be serialized with Drupal\Core\DependencyInjection\DependencySerializationTrait.',
                    22,
                    'See https://www.drupal.org/node/3110266',
                ],
                [
                    'Read only properties cannot be serialized with Drupal\Core\DependencyInjection\DependencySerializationTrait.',
                    22,
                    'See https://www.drupal.org/node/3110266',
                ],
                [
                    'Read only properties cannot be serialized with Drupal\Core\DependencyInjection\DependencySerializationTrait.',
                    28,
                    'See https://www.drupal.org/node/3110266',
                ],
                [
                    'Private properties cannot be serialized with Drupal\Core\DependencyInjection\DependencySerializationTrait.',
                    32,
                    'See https://www.drupal.org/node/3110266',
                ],
            ]
        );
    }
}
