<?php declare(strict_types=1);

namespace PHPStan\Drupal;

final class EntityTestClass extends AnalyzerTestBase
{
    /**
     * @dataProvider dataEntitySamples
     */
    public function testEntityFields(string $path, int $count, array $errorMessages) {
        $errors = $this->runAnalyze($path);
        $this->assertCount($count, $errors, print_r($errors, true));
        foreach ($errors as $key => $error) {
            $this->assertEquals($errorMessages[$key], $error->getMessage());
        }
    }


    public function dataEntitySamples(): \Generator
    {
        yield [
            __DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/src/EntityFieldReflection/EntityFieldMagicalGetters.php',
            2,
            [
                'Access to an undefined property Drupal\Core\TypedData\TypedDataInterface::$value.',
                'Access to an undefined property Drupal\Core\TypedData\TypedDataInterface::$value.',
            ]
        ];
        yield [
            __DIR__ . '/../fixtures/drupal/modules/phpstan_fixtures/src/EntityFieldReflection/EntityFieldOriginalProperty.php',
            0,
            []
        ];
    }
}
