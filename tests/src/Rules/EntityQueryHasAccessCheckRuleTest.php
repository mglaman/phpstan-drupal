<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\EntityQuery\EntityQueryHasAccessCheckRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class EntityQueryHasAccessCheckRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new EntityQueryHasAccessCheckRule();
    }

    /**
     * @dataProvider cases
     */
    public function test(array $files, array $errors): void
    {
        $this->analyse($files, $errors);
    }

    public function cases(): \Generator
    {
        yield [
            [__DIR__.'/../../fixtures/drupal/modules/phpstan_fixtures/src/EntityQueryWithAccessRule.php'],
            [],
        ];

        yield [
            [__DIR__.'/../../fixtures/drupal/modules/phpstan_fixtures/src/EntityQueryWithoutAccessRule.php'],
            [
                [
                    'Missing explicit access check on entity query.',
                    11,
                    'See https://www.drupal.org/node/3201242',
                ],
                [
                    'Missing explicit access check on entity query.',
                    19,
                    'See https://www.drupal.org/node/3201242',
                ],
            ],
        ];

        yield 'bug-438.php' => [
            [__DIR__ . '/data/bug-438.php'],
            []
        ];
        yield 'bug-396a1.php' => [
            [__DIR__.'/data/bug-396a1.php'],
            [
                [
                    'Missing explicit access check on entity query.',
                    27,
                    'See https://www.drupal.org/node/3201242',
                ]
            ]
        ];

        // @todo 396a2 this passes when run individually, somehow.
        /*
        yield 'bug-396a2.php' => [
            [__DIR__ . '/data/bug-396a2.php'],
            []
        ];*/
        // @todo not chained call, return type extension has no influence.
        /*
        yield 'bug-396a3.php' => [
            [__DIR__ . '/data/bug-396a3.php'],
            []
        ];*/
        yield 'bug-396b.php' => [
            [__DIR__ . '/data/bug-396b.php'],
            []
        ];
        yield 'bug-396c.php' => [
            [__DIR__ . '/data/bug-396c.php'],
            []
        ];
        // @todo Try to resolve from typed property.
        /*
        yield 'bug-437.php' => [
            [__DIR__ . '/data/bug-437.php'],
            []
        ];*/

        yield 'bug-475.php' => [
            [__DIR__.'/data/bug-475.php'],
            []
        ];

        yield 'bug-475b.php' => [
            [__DIR__.'/data/bug-475b.php'],
            []
        ];
    }
}
