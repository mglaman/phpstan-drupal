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

    public static function cases(): \Generator
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

        yield 'bug-516.php' => [
            [__DIR__.'/data/bug-516.php'],
            []
        ];

        yield 'bug-547.php' => [
            [__DIR__.'/data/bug-547.php'],
            []
        ];

        yield 'bug-177.php' => [
            [__DIR__.'/data/bug-177.php'],
            [
                [
                    'File core/modules/locale/locale.fetch.php could not be loaded from Drupal\Core\Extension\ModuleHandlerInterface::loadInclude',
                    6
                ],
                [
                    'File core/modules/locale/locale.fetch.php could not be loaded from Drupal\Core\Extension\ModuleHandlerInterface::loadInclude',
                    8
                ],
            ]
        ];
    }

}
