<?php declare(strict_types=1);

namespace PHPStan\Drupal;

use PHPStan\Analyser\Error;

final class DrupalIntegrationTest extends AnalyzerTestBase {

    public function testInstallPhp(): void
    {
        $errors = $this->runAnalyze(__DIR__ . '/../fixtures/drupal/core/install.php');
        self::assertCount(0, $errors->getErrors());
        self::assertCount(0, $errors->getInternalErrors());
    }

    public function testTestSuiteAutoloading() {
        $paths = [
            __DIR__ . '/../fixtures/drupal/core/tests/TestSuites/FunctionalJavascriptTestSuite.php',
            __DIR__ . '/../fixtures/drupal/core/tests/TestSuites/FunctionalTestSuite.php',
            __DIR__ . '/../fixtures/drupal/core/tests/TestSuites/KernelTestSuite.php',
            __DIR__ . '/../fixtures/drupal/core/tests/TestSuites/UnitTestSuite.php',
        ];
        foreach ($paths as $path) {
            $errors = $this->runAnalyze($path);
            $this->assertCount(1, $errors->getErrors(), $path);
            $this->assertEquals('Unsafe usage of new static().', $errors->getErrors()[0]->getMessage());
            $this->assertCount(0, $errors->getInternalErrors(), print_r($errors->getInternalErrors(), true));
        }

        // Abstract doesn't warn on static constructor.
        $errors = $this->runAnalyze(__DIR__ . '/../fixtures/drupal/core/tests/TestSuites/TestSuiteBase.php');
        $this->assertCount(0, $errors->getErrors(), $path);
        $this->assertCount(0, $errors->getInternalErrors(), print_r($errors->getInternalErrors(), true));
    }

    public function testDrupalTestInChildSiteContant() {
        $errors = $this->runAnalyze(__DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/src/DrupalTestInChildSiteContant.php');
        $this->assertCount(0, $errors->getErrors());
        $this->assertCount(0, $errors->getInternalErrors());
    }

    public function testExtensionReportsError() {
        $is_d9 = version_compare('9.0.0', \Drupal::VERSION) !== 1;
        $errors = $this->runAnalyze(__DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/phpstan_fixtures.module');
        self::assertCount(3, $errors->getErrors(), var_export($errors, true));
        self::assertCount(0, $errors->getInternalErrors(), var_export($errors, true));

        $errors = $errors->getErrors();
        $error = array_shift($errors);
        self::assertEquals('If condition is always false.', $error->getMessage());
        $error = array_shift($errors);
        self::assertEquals('Function phpstan_fixtures_MissingReturnRule() should return string but return statement is missing.', $error->getMessage());
        $error = array_shift($errors);
        self::assertNotFalse(strpos($error->getMessage(), 'phpstan_fixtures/phpstan_fixtures.fetch.inc could not be loaded from Drupal\\Core\\Extension\\ModuleHandlerInterface::loadInclude'));
    }

    public function testExtensionTestSuiteAutoloading(): void
    {
        $paths = [
            __DIR__ . '/../fixtures/drupal/modules/module_with_tests/tests/src/Unit/ModuleWithTestsTest.php',
            __DIR__ . '/../fixtures/drupal/modules/module_with_tests/tests/src/Traits/ModuleWithTestsTrait.php',
            __DIR__ . '/../fixtures/drupal/modules/module_with_tests/tests/src/TestSite/ModuleWithTestsTestSite.php',
            __DIR__ . '/../fixtures/drupal/modules/module_with_tests/tests/src/Kernel/DrupalStaticCallTest.php',
        ];
        foreach ($paths as $path) {
            $errors = $this->runAnalyze($path);
            self::assertCount(0, $errors->getErrors(), implode(PHP_EOL, array_map(static function (Error $error) {
                return $error->getMessage();
            }, $errors->getErrors())));
            self::assertCount(0, $errors->getInternalErrors(), implode(PHP_EOL, $errors->getInternalErrors()));
        }
    }

    public function testServiceMapping8()
    {
        if (version_compare('9.0.0', \Drupal::VERSION) !== 1) {
            $this->markTestSkipped('Only tested on Drupal 8.x.x');
        }
        $errorMessages = [
            '\Drupal calls should be avoided in classes, use dependency injection instead',
            'Call to an undefined method Drupal\Core\Entity\EntityManager::thisMethodDoesNotExist().',
            'Call to deprecated method getDefinitions() of class Drupal\\Core\\Entity\\EntityManager:
in drupal:8.0.0 and is removed from drupal:9.0.0.
  Use \\Drupal\\Core\\Entity\\EntityTypeManagerInterface::getDefinitions()
  instead.'
        ];
        $errors = $this->runAnalyze(__DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/src/TestServicesMappingExtension.php');
        $this->assertCount(3, $errors->getErrors());
        $this->assertCount(0, $errors->getInternalErrors());
        foreach ($errors->getErrors() as $key => $error) {
            $this->assertEquals($errorMessages[$key], $error->getMessage());
        }
    }

    public function testServiceMapping9()
    {
        if (version_compare('9.0.0', \Drupal::VERSION) === 1) {
            $this->markTestSkipped('Only tested on Drupal 9.x.x');
        }
        // @todo: the actual error should be the fact `entity.manager` does not exist.
        $errorMessages = [
            '\Drupal calls should be avoided in classes, use dependency injection instead',
        ];
        $errors = $this->runAnalyze(__DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/src/TestServicesMappingExtension.php');
        $this->assertCount(1, $errors->getErrors());
        $this->assertCount(0, $errors->getInternalErrors());
        foreach ($errors->getErrors() as $key => $error) {
            $this->assertEquals($errorMessages[$key], $error->getMessage());
        }
    }

    public function testAppRootPseudoService() {
        $errors = $this->runAnalyze(__DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/src/AppRootParameter.php');
        $this->assertCount(0, $errors->getErrors(), var_export($errors, TRUE));
        $this->assertCount(0, $errors->getInternalErrors(), var_export($errors, TRUE));
    }

    public function testThemeSettingsFile() {
        $errors = $this->runAnalyze(__DIR__ . '/../fixtures/drupal/core/modules/system/tests/themes/test_theme_settings/theme-settings.php');
        $this->assertCount(0, $errors->getErrors(), var_export($errors, TRUE));
        $this->assertCount(0, $errors->getInternalErrors(), var_export($errors, TRUE));
    }

    public function testModuleLoadInclude() {
        $errors = $this->runAnalyze(__DIR__ . '/../fixtures/drupal/modules/module_load_include_fixture/module_load_include_fixture.module');
        $this->assertCount(0, $errors->getErrors(), var_export($errors, TRUE));
        $this->assertCount(0, $errors->getInternalErrors(), var_export($errors, TRUE));
    }
}
