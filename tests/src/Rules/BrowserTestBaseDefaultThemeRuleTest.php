<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\Tests\BrowserTestBaseDefaultThemeRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

/**
 * @extends \mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase<\mglaman\PHPStanDrupal\Rules\Drupal\Tests\BrowserTestBaseDefaultThemeRule>
 */
final class BrowserTestBaseDefaultThemeRuleTest extends DrupalRuleTestCase {

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new BrowserTestBaseDefaultThemeRule();
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
            __DIR__ . '/../../fixtures/drupal/modules/module_with_tests/tests/src/Functional/MissingDefaultThemeTest.php',
            [
                [
                    'Drupal\Tests\BrowserTestBase::$defaultTheme is required. See https://www.drupal.org/node/3083055, which includes recommendations on which theme to use.',
                    07
                ],
            ]
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/module_with_tests/tests/src/Functional/DefaultThemeTest.php',
            []
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/module_with_tests/tests/src/Functional/ExtendsDefaultThemeTest.php',
            []
        ];
    }

}
