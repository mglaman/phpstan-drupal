<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Deprecations\CallToDeprecatedMethodRule;
use PHPStan\Rules\Deprecations\DeprecatedScopeHelper;
use PHPStan\Rules\Rule;

final class RevisionableStorageInterfaceStubTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        // @phpstan-ignore-next-line
        return new CallToDeprecatedMethodRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(DeprecatedScopeHelper::class)
        );
    }

    public function testRule(): void
    {
        $errors = [];
        $drupalVersion = str_replace('-dev', '', \Drupal::VERSION);
        if (version_compare($drupalVersion, '10.1', '>=')) {
            // There's a quirk on 10.1.x+ which false reports this error but was fixed on 11.x.
            if (version_compare($drupalVersion, '10.2', '<')) {
                $errors[] = [
                    'Call to deprecated method loadRevision() of class Drupal\Core\Entity\EntityStorageInterface:
in drupal:10.1.0 and is removed from drupal:11.0.0. Use
\Drupal\Core\Entity\RevisionableStorageInterface::loadRevision instead.',
                    12
                ];
            }
            $errors[] = [
                'Call to deprecated method loadRevision() of class Drupal\Core\Entity\EntityStorageInterface:
in drupal:10.1.0 and is removed from drupal:11.0.0. Use
\Drupal\Core\Entity\RevisionableStorageInterface::loadRevision instead.',
                15
            ];
        }
        $this->analyse(
            [__DIR__ . '/data/bug-586.php'],
            $errors
        );
    }

}
