<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\LoadIncludes;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class LoadIncludesRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return self::getContainer()->getByType(LoadIncludes::class);
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
            [
                __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/phpstan_fixtures.module'
            ],
            [
                [
                    'File modules/phpstan_fixtures/phpstan_fixtures.fetch.inc could not be loaded from Drupal\Core\Extension\ModuleHandlerInterface::loadInclude',
                    30
                ]
            ],
        ];

        yield [
            [
                __DIR__ . '/../../fixtures/drupal/core/tests/Drupal/Tests/Core/Form/FormStateTest.php'
            ],
            [],
        ];

        yield 'bug-x.php' => [
            [__DIR__.'/data/bug-x.php'],
            []
        ];
    }

}
