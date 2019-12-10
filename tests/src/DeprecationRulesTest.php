<?php declare(strict_types=1);

namespace PHPStan\Drupal;

class DeprecationRulesTest extends AnalyzerTestBase
{

    /**
     * @dataProvider dataDeprecatedSamples
     */
    public function testDeprecationRules(string $path, int $count, array $errorMessages)
    {
        $errors = $this->runAnalyze($path);
        $this->assertCount($count, $errors);
        foreach ($errors as $key => $error) {
            $this->assertEquals($errorMessages[$key], $error->getMessage());
        }
    }

    public function dataDeprecatedSamples(): \Generator
    {
        yield [
            __DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/src/UsesDeprecatedUrlFunction.php',
            2,
            [
                '\Drupal calls should be avoided in classes, use dependency injection instead',
                'Call to deprecated method url() of class Drupal:
in drupal:8.0.0 and is removed from drupal:9.0.0.
Instead create a \Drupal\Core\Url object directly, for example using
Url::fromRoute().'
            ]
        ];
        yield [
            __DIR__ . '/../fixtures/drupal/core/lib/Drupal/Core/Entity/EntityManager.php',
            2,
            [
                'Class Drupal\Core\Entity\EntityManager implements deprecated interface Drupal\Core\Entity\EntityManagerInterface:
in drupal:8.0.0 and is removed from drupal:9.0.0.',
                'Method Drupal\Core\Entity\EntityManager::clearDisplayModeInfo() should return $this(Drupal\Core\Entity\EntityManager) but return statement is missing.'
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
        yield [
            __DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/src/UsesDeprecatedServiceMethod.php',
            2,
            [
                '\Drupal calls should be avoided in classes, use dependency injection instead',
                'Call to deprecated method getDefinitions() of class',
            ]
        ];
    }
}
