<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\EntityStorageDirectInjectionRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class EntityStorageDirectInjectionRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new EntityStorageDirectInjectionRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/entity-storage-direct-injection.php'],
            [
                [
                    'Direct injection of entity storage via $nodeStorage is not recommended. Inject Drupal\Core\Entity\EntityTypeManagerInterface and call getStorage() at the call-site instead.',
                    15,
                    'See https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal',
                ],
                [
                    'Direct injection of entity storage via $storage is not recommended. Inject Drupal\Core\Entity\EntityTypeManagerInterface and call getStorage() at the call-site instead.',
                    24,
                    'See https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal',
                ],
                [
                    'Direct injection of entity storage via $storage is not recommended. Inject Drupal\Core\Entity\EntityTypeManagerInterface and call getStorage() at the call-site instead.',
                    34,
                    'See https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal',
                ],
            ]
        );
    }
}
