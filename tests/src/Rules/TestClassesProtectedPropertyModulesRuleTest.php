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
     */
    public function test(array $files, array $errors): void
    {
        $this->analyse($files, $errors);
    }

    public function cases(): \Generator
    {
        yield [
            [__DIR__ . '/data/test-cases-test-classes-protected-property-modules-rule.php'],
            [
                [
                    'Declaring the ::$modules property as non-protected in TestCasesTestClassesProtectedPropertyModulesRule\PublicStaticPropertyModulesClass is required.
    💡 Change record: https://www.drupal.org/node/2909426',
                    25
                ],
            ],
        ];
    }

}
