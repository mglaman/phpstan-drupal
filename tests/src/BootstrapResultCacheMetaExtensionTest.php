<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use mglaman\PHPStanDrupal\Drupal\BootstrapResultCacheMetaExtension;
use mglaman\PHPStanDrupal\Drupal\ConfigSchemaData;
use mglaman\PHPStanDrupal\Drupal\Extension;
use mglaman\PHPStanDrupal\Drupal\ExtensionMap;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PHPUnit\Framework\TestCase;

/**
 * The seeded ServiceMap, ExtensionMap, and ConfigSchemaData state is static,
 * so each test runs in its own process to avoid clobbering the
 * bootstrap-populated state other test classes depend on.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class BootstrapResultCacheMetaExtensionTest extends TestCase
{

    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $fixtureRoot = \realpath(__DIR__ . '/../fixtures/drupal');
        self::assertNotFalse($fixtureRoot);
        $this->fixtureRoot = $fixtureRoot;
    }

    private function createExtension(): BootstrapResultCacheMetaExtension
    {
        return new BootstrapResultCacheMetaExtension(
            new ServiceMap(),
            new ExtensionMap(),
            new ConfigSchemaData()
        );
    }

    /**
     * The ServiceMap, ExtensionMap, and ConfigSchemaData state is static, so
     * every test must seed all of it to be independent of test order.
     *
     * @param array<int, Extension> $modules
     * @param array<string, string> $serviceYamlPaths
     * @param list<string> $schemaDirectories
     */
    private function seed(array $modules = [], array $serviceYamlPaths = [], array $schemaDirectories = []): void
    {
        (new ServiceMap())->setDrupalServices([], $serviceYamlPaths);
        (new ExtensionMap())->setExtensions($modules, [], []);
        (new ConfigSchemaData())->setSchemaDirectories($schemaDirectories);
    }

    private function createModule(string $name): Extension
    {
        return new Extension(
            $this->fixtureRoot,
            'module',
            "modules/$name/$name.info.yml",
            "$name.module"
        );
    }

    public function testKey(): void
    {
        self::assertSame('phpstan-drupal', $this->createExtension()->getKey());
    }

    public function testHashIsDeterministic(): void
    {
        $this->seed([$this->createModule('phpstan_fixtures')]);
        $extension = $this->createExtension();
        self::assertSame($extension->getHash(), $extension->getHash());
    }

    public function testExtensionListChangesHash(): void
    {
        $this->seed([$this->createModule('phpstan_fixtures')]);
        $hashBefore = $this->createExtension()->getHash();

        $this->seed([
            $this->createModule('phpstan_fixtures'),
            $this->createModule('module_with_dependencies'),
        ]);
        $hashAfter = $this->createExtension()->getHash();

        self::assertNotSame($hashBefore, $hashAfter);
    }

    public function testServicesYmlContentChangesHash(): void
    {
        $servicesYml = \tempnam(\sys_get_temp_dir(), 'phpstan_drupal_test');
        self::assertNotFalse($servicesYml);
        \file_put_contents($servicesYml, "services:\n  foo.bar:\n    class: Drupal\Foo\Bar\n");

        $this->seed([], ['test_module' => $servicesYml]);
        $hashBefore = $this->createExtension()->getHash();

        \file_put_contents($servicesYml, "services:\n  foo.baz:\n    class: Drupal\Foo\Baz\n", FILE_APPEND);
        $hashAfter = $this->createExtension()->getHash();
        \unlink($servicesYml);

        self::assertNotSame($hashBefore, $hashAfter);
    }

    public function testMissingFileStillProducesDeterministicHash(): void
    {
        $this->seed([], ['test_module' => '/nonexistent/test_module.services.yml']);
        $extension = $this->createExtension();
        $hashWithMissingFile = $extension->getHash();
        self::assertSame($hashWithMissingFile, $extension->getHash());

        $this->seed([], []);
        self::assertNotSame($hashWithMissingFile, $this->createExtension()->getHash());
    }

    public function testSchemaDirectoryChangesHash(): void
    {
        $schemaDir = \sys_get_temp_dir() . '/phpstan_drupal_schema_' . \uniqid();
        \mkdir($schemaDir);
        \file_put_contents($schemaDir . '/test.schema.yml', "test.settings:\n  type: config_object\n");

        $this->seed([], [], []);
        $hashBefore = $this->createExtension()->getHash();

        $this->seed([], [], [$schemaDir]);
        $hashAfter = $this->createExtension()->getHash();

        \unlink($schemaDir . '/test.schema.yml');
        \rmdir($schemaDir);

        self::assertNotSame($hashBefore, $hashAfter);
    }
}
