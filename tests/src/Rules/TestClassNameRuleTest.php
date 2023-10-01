<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\Tests\TestClassNameRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

class TestClassNameRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new TestClassNameRule();
    }

    /**
     * @dataProvider fileData
     */
    public function testRule(string $path, array $errorMessages): void
    {
        $this->analyse(
            [$path],
            $errorMessages
        );
    }

    public function fileData(): \Generator
    {
        yield [
            __DIR__ . '/data/test-cases-TestClassNameRule.php',
            [
                [
                    'Non-abstract test classes names should always have the suffix "Test".',
                    29,
                    'See https://www.drupal.org/docs/develop/standards/php/object-oriented-code#naming'
                ],
                [
                    'Non-abstract test classes names should always have the suffix "Test".',
                    39,
                    'See https://www.drupal.org/docs/develop/standards/php/object-oriented-code#naming'
                ],
            ]
        ];
    }

}
