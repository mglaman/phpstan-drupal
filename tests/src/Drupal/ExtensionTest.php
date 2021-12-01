<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Drupal;

use mglaman\PHPStanDrupal\Drupal\Extension;
use PHPUnit\Framework\TestCase;

final class ExtensionTest extends TestCase
{

    /**
     * @dataProvider dependenciesProvider
     */
    public function testDependencies(array $dependencies, string $pathInfoFile): void
    {
        self::assertSame(
            $dependencies,
            (new Extension(__DIR__ . '/../../fixtures/drupal', 'module', $pathInfoFile))->getDependencies()
        );
    }

    public function dependenciesProvider(): \Generator
    {
        yield [
            [
                'node',
                'bar',
                'service_map',
            ],
            '/modules/module_with_dependencies/module_with_dependencies.info.yml',
        ];
        yield [
            [],
            '/modules/module_without_dependencies/module_without_dependencies.info.yml'
        ];
    }
}
