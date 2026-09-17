<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\EntityStoragePropertyAssignmentRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class EntityStoragePropertyAssignmentRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new EntityStoragePropertyAssignmentRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/entity-storage-property-assignment.php'],
            [
                [
                    'Storing entity storage as a class property is not recommended. Call Drupal\Core\Entity\EntityTypeManagerInterface::getStorage() at the call-site instead.',
                    19,
                    'See https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal',
                ],
                [
                    'Storing entity storage as a class property is not recommended. Call Drupal\Core\Entity\EntityTypeManagerInterface::getStorage() at the call-site instead.',
                    30,
                    'See https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal',
                ],
                [
                    'Storing entity storage as a class property is not recommended. Call Drupal\Core\Entity\EntityTypeManagerInterface::getStorage() at the call-site instead.',
                    45,
                    'See https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal',
                ],
                [
                    'Storing entity storage as a class property is not recommended. Call Drupal\Core\Entity\EntityTypeManagerInterface::getStorage() at the call-site instead.',
                    57,
                    'See https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal',
                ],
                [
                    'Storing entity storage as a class property is not recommended. Call Drupal\Core\Entity\EntityTypeManagerInterface::getStorage() at the call-site instead.',
                    118,
                    'See https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal',
                ],
            ]
        );
    }

    public function testBug1012(): void
    {
        $this->analyse([__DIR__ . '/data/bug-1012-proxy.php'], []);
    }
}
