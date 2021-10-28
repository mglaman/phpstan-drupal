<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use mglaman\PHPStanDrupal\Tests\Rules\DummyRule;

final class ServiceProviderAutoloadingTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new DummyRule();
    }

    /**
     * @dataProvider dataFixtures
     */
    public function testLoadingServiceProvider(string $path, array $errorMessages) {
        $this->analyse([$path], $errorMessages);
    }


    public function dataFixtures(): \Generator
    {
        yield [
            __DIR__ . '/../fixtures/drupal/modules/service_provider_test/src/ServiceProviderTestServiceProvider.php',
            []
        ];
    }
}
