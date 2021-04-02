<?php declare(strict_types=1);

namespace PHPStan\Drupal;

class DeprecationRulesTest extends AnalyzerTestBase
{

    /**
     * @dataProvider dataDeprecatedSamples
     */
    public function testDeprecationRules(string $path, int $count, array $errorMessages)
    {
        if (version_compare('9.0.0', \Drupal::VERSION) !== 1) {
            $this->markTestSkipped('Only tested on Drupal 8.x.x');
        }
        $errors = $this->runAnalyze($path);
        $this->assertCount($count, $errors->getErrors(), var_export($errors, true));
        foreach ($errors->getErrors() as $key => $error) {
            $this->assertEquals($errorMessages[$key], $error->getMessage());
        }
    }

    public function dataDeprecatedSamples(): \Generator
    {
        yield [
            __DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/src/UsesDeprecatedUrlFunction.php',
            2,
            [
                'Call to deprecated method url() of class Drupal:
in drupal:8.0.0 and is removed from drupal:9.0.0.
  Instead create a \Drupal\Core\Url object directly, for example using
  Url::fromRoute().',
                '\Drupal calls should be avoided in classes, use dependency injection instead',
            ]
        ];
        yield [
            __DIR__ . '/../fixtures/drupal/core/lib/Drupal/Core/Entity/EntityManager.php',
            2,
            [
                'Class Drupal\Core\Entity\EntityManager implements deprecated interface Drupal\Core\Entity\EntityManagerInterface:
in drupal:8.0.0 and is removed from drupal:9.0.0.',
                'Method Drupal\\Core\\Entity\\EntityManager::setFieldMap() should return $this(Drupal\\Core\\Entity\\EntityManager) but returns Drupal\\Core\\Entity\\EntityFieldManager.',
            ]
        ];
        yield [
            __DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/src/DeprecatedGlobalConstants.php',
            2,
            [
                'Call to deprecated constant DATETIME_STORAGE_TIMEZONE: Deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::STORAGE_TIMEZONE instead.',
                'Call to deprecated constant DATETIME_DATE_STORAGE_FORMAT: Deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use \Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface::DATE_STORAGE_FORMAT instead.',
            ]
        ];
    }
}
