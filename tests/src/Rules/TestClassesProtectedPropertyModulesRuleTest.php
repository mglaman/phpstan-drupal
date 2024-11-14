<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\TestClassesProtectedPropertyModulesRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

class TestClassesProtectedPropertyModulesRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new TestClassesProtectedPropertyModulesRule();
    }

    /**
     * @dataProvider cases
     *
     * @param list<array{0: string, 1: int, 2?: string|null}> $errors
     */
    public function test(array $files, array $errors): void
    {
        $this->analyse($files, $errors);
    }

    public static function cases(): \Generator
    {
        yield [
            [__DIR__ . '/data/test-cases-test-classes-protected-property-modules-rule.php'],
            [
                [
                    'Property TestCasesTestClassesProtectedPropertyModulesRule\PublicStaticPropertyModulesClass::$modules property must be protected.',
                    25,
                    'Change record: https://www.drupal.org/node/2909426',
                ],
            ],
        ];
    }

}
