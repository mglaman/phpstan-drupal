<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use mglaman\PHPStanDrupal\Tests\Rules\DummyRule;

final class DrupalAutoloadingTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new DummyRule();
    }

    /**
     * @dataProvider dataFixtures
     */
    public function testAnalyze(array $paths, array $errorMessages): void
    {
        $this->analyse($paths, $errorMessages);
    }


    public static function dataFixtures(): \Generator
    {
        yield [
            [__DIR__ . '/../fixtures/drupal/modules/service_provider_test/src/ServiceProviderTestServiceProvider.php'],
            []
        ];
        yield [
            [__DIR__ . '/../fixtures/drupal/core/install.php'],
            []
        ];
        yield [
            [__DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/src/DrupalTestInChildSiteContant.php'],
            []
        ];
        yield [
            [__DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/phpstan_fixtures.module'],
            []
        ];
        yield [
            [__DIR__ . '/../fixtures/drupal/core/modules/system/tests/themes/test_theme_settings/theme-settings.php'],
            []
        ];
        yield [
            [
                // @todo commented out files cause error on Unwind object to string?
                // __DIR__ . '/../fixtures/drupal/modules/module_with_tests/tests/src/Unit/ModuleWithTestsTest.php',
                __DIR__ . '/../fixtures/drupal/modules/module_with_tests/tests/src/Traits/ModuleWithTestsTrait.php',
                __DIR__ . '/../fixtures/drupal/modules/module_with_tests/tests/src/TestSite/ModuleWithTestsTestSite.php',
                // __DIR__ . '/../fixtures/drupal/modules/module_with_tests/tests/src/Kernel/DrupalStaticCallTest.php',
                __DIR__ . '/../fixtures/drupal/core/tests/TestSuites/TestSuiteBase.php',
            ],
            []
        ];
    }
}
