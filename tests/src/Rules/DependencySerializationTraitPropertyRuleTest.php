<?php

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\DependencySerializationTraitPropertyRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;

final class DependencySerializationTraitPropertyRuleTest extends DrupalRuleTestCase
{

    private int $phpVersionId = 80300;

    protected function getRule(): Rule
    {
        return new DependencySerializationTraitPropertyRule(new PhpVersion($this->phpVersionId));
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
                    'Drupal\Core\DependencyInjection\DependencySerializationTrait does not support private properties.',
                    32,
                    'See https://www.drupal.org/node/3110266',
                ],
                [
                    'Read-only properties are incompatible with Drupal\Core\DependencyInjection\DependencySerializationTrait when the trait is used by a parent class on PHP < 8.4.',
                    37,
                    'Drupal\Core\DependencyInjection\DependencySerializationTrait::__wakeup() cannot initialize a read-only property declared in a child class. Use the trait in this class directly, or remove the readonly modifier.',
                ],
            ]
        );
    }

    public function testReadOnlyPropertiesAllowedOnPhp84(): void
    {
        $this->phpVersionId = 80400;
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
                    'Drupal\Core\DependencyInjection\DependencySerializationTrait does not support private properties.',
                    32,
                    'See https://www.drupal.org/node/3110266',
                ],
            ]
        );
    }
}
