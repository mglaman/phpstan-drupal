<?php declare(strict_types=1);

namespace PHPStan\Drupal;

final class ServiceProviderAutoloadingTest extends AnalyzerTestBase
{
    /**
     * @dataProvider dataEntitySamples
     */
    public function testLoadingServiceProvider(string $path, int $count, array $errorMessages) {
        $errors = $this->runAnalyze($path);
        $this->assertCount($count, $errors, print_r($errors, true));
        foreach ($errors as $key => $error) {
            $this->assertEquals($errorMessages[$key], $error->getMessage());
        }
    }


    public function dataEntitySamples(): \Generator
    {
        yield [
            __DIR__ . '/../fixtures/drupal/modules/service_provider_test/src/ServiceProviderTestServiceProvider.php',
            0,
            []
        ];
    }
}
