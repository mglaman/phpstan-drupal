<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\Tests\TestClassSuffixNameRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

class TestClassSuffixNameRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new TestClassSuffixNameRule();
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
            __DIR__ . '/data/test-cases-TestClassSuffixNameRule.php',
            [
                [
                    'Non-abstract test classes names should always have the suffix "Test", found incorrect class name "IncorrectlyNamedDirectlyExtendingPHPUnitFrameworkTestCaseClass".',
                    29,
                    'See https://www.drupal.org/docs/develop/standards/php/object-oriented-code#naming'
                ],
                [
                    'Non-abstract test classes names should always have the suffix "Test", found incorrect class name "IncorrectlyNamedIndirectlyExtendingPHPUnitFrameworkTestCaseClass".',
                    39,
                    'See https://www.drupal.org/docs/develop/standards/php/object-oriented-code#naming'
                ],
            ]
        ];
    }

}
