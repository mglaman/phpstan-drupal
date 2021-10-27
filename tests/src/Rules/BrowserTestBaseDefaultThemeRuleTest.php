<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\AnalyzerTestBase;

final class BrowserTestBaseDefaultThemeRuleTest extends AnalyzerTestBase {

    /**
     * @dataProvider fileData
     */
    public function testRule(string $path, int $count, array $errorMessages): void
    {
        $errors = $this->runAnalyze($path);
        self::assertCount($count, $errors->getErrors(), var_export($errors, true));
        foreach ($errors->getErrors() as $key => $error) {
            self::assertEquals($errorMessages[$key], $error->getMessage());
        }
    }

    public function fileData(): \Generator
    {
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/module_with_tests/tests/src/Functional/MissingDefaultThemeTest.php',
            1,
            [
                'Drupal\Tests\BrowserTestBase::$defaultTheme is required. See https://www.drupal.org/node/3083055, which includes recommendations on which theme to use.',
            ]
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/module_with_tests/tests/src/Functional/DefaultThemeTest.php',
            0,
            []
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/module_with_tests/tests/src/Functional/ExtendsDefaultThemeTest.php',
            0,
            []
        ];
    }


}
