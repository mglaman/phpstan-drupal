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
                    'Drupal\Core\DependencyInjection\DependencySerializationTrait does not support private properties.',
                    16,
                    'See https://www.drupal.org/node/3110266',
                ],
                [
                    'Drupal\Core\DependencyInjection\DependencySerializationTrait does not support private properties.',
                    22,
                    'See https://www.drupal.org/node/3110266',
                ],
                [
                    'Read-only properties are incompatible with Drupal\Core\DependencyInjection\DependencySerializationTrait.',
                    22,
                    'See https://www.drupal.org/node/3110266',
                ],
                [
                    'Read-only properties are incompatible with Drupal\Core\DependencyInjection\DependencySerializationTrait.',
                    28,
                    'See https://www.drupal.org/node/3110266',
                ],
                [
                    'Drupal\Core\DependencyInjection\DependencySerializationTrait does not support private properties.',
                    32,
                    'See https://www.drupal.org/node/3110266',
                ],
            ]
        );
    }
}
