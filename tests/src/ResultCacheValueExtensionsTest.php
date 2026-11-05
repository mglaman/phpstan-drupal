<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use mglaman\PHPStanDrupal\Drupal\ConfigSchemaResultCacheValueExtension;
use mglaman\PHPStanDrupal\Drupal\DrupalSite;
use mglaman\PHPStanDrupal\Drupal\Extension;
use mglaman\PHPStanDrupal\Drupal\ExtensionListResultCacheValueExtension;
use mglaman\PHPStanDrupal\Drupal\ServiceResultCacheValueExtension;
use PHPUnit\Framework\TestCase;

final class ResultCacheValueExtensionsTest extends TestCase
{

    /**
     * @param array<string, mixed> $services
     * @param list<Extension> $modules
     * @param array<string, Extension> $themes
     * @param list<string> $schemaDirectories
     */
    private function createSite(array $services = [], array $modules = [], array $themes = [], array $schemaDirectories = []): DrupalSite
    {
        return new class ($services, $modules, $themes, $schemaDirectories) extends DrupalSite {
            /**
             * @param array<string, mixed> $services
             * @param list<Extension> $moduleList
             * @param array<string, Extension> $themeList
             * @param list<string> $schemaDirectories
             */
            public function __construct(
                private array $services,
                private array $moduleList,
                private array $themeList,
                private array $schemaDirectories
            ) {
            }

            public function getServiceDefinitions(): array
            {
                return $this->services;
            }

            public function getModules(): array
            {
                return $this->moduleList;
            }

            public function getThemes(): array
            {
                return $this->themeList;
            }

            public function getProfiles(): array
            {
                return [];
            }

            public function getSchemaDirectories(): array
            {
                return $this->schemaDirectories;
            }
        };
    }

    /**
     * @param array<string, mixed> $services
     */
    private function serviceValue(array $services, string $id = 'foo'): string
    {
        return (new ServiceResultCacheValueExtension($this->createSite($services)))->getValue($id);
    }

    public function testServiceValueIsStable(): void
    {
        $services = ['foo' => ['class' => 'Drupal\foo\Foo']];
        self::assertSame($this->serviceValue($services), $this->serviceValue($services));
    }

    public function testMissingServiceHasAValue(): void
    {
        // A service that starts to exist has to re-analyze the files asking.
        $missing = $this->serviceValue([]);
        self::assertSame('missing', $missing);
        self::assertNotSame($missing, $this->serviceValue(['foo' => ['class' => 'Drupal\foo\Foo']]));
    }

    /**
     * @dataProvider serviceChangesThatDoNotMatter
     *
     * @param array<string, mixed> $changed
     */
    public function testServiceValueIgnores(array $changed): void
    {
        self::assertSame($this->serviceValue(['foo' => ['class' => 'Drupal\foo\Foo']]), $this->serviceValue($changed));
    }

    public static function serviceChangesThatDoNotMatter(): \Generator
    {
        yield 'unrelated service' => [[
            'foo' => ['class' => 'Drupal\foo\Foo'],
            'bar' => ['class' => 'Drupal\bar\Bar'],
        ]];
        yield 'arguments' => [['foo' => ['class' => 'Drupal\foo\Foo', 'arguments' => ['@bar']]]];
        yield 'tags' => [['foo' => ['class' => 'Drupal\foo\Foo', 'tags' => [['name' => 'bar']]]]];
    }

    /**
     * @dataProvider serviceChangesThatMatter
     *
     * @param array<string, mixed> $changed
     */
    public function testServiceValueChangesWith(array $changed): void
    {
        self::assertNotSame($this->serviceValue(['foo' => ['class' => 'Drupal\foo\Foo']]), $this->serviceValue($changed));
    }

    public static function serviceChangesThatMatter(): \Generator
    {
        yield 'class' => [['foo' => ['class' => 'Drupal\foo\Other']]];
        yield 'public' => [['foo' => ['class' => 'Drupal\foo\Foo', 'public' => false]]];
        yield 'deprecated' => [['foo' => ['class' => 'Drupal\foo\Foo', 'deprecated' => 'Do not use.']]];
        yield 'alias shortcut' => [['foo' => '@bar']];
        yield 'decorator' => [[
            'foo' => ['class' => 'Drupal\foo\Foo'],
            'decorator' => ['class' => 'Drupal\foo\Decorator', 'decorates' => 'foo'],
        ]];
    }

    /**
     * @dataProvider relatedServices
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    public function testServiceValueFollows(array $before, array $after): void
    {
        self::assertNotSame($this->serviceValue($before), $this->serviceValue($after));
    }

    public static function relatedServices(): \Generator
    {
        yield 'alias target' => [
            ['foo' => '@bar', 'bar' => ['class' => 'Drupal\foo\First']],
            ['foo' => '@bar', 'bar' => ['class' => 'Drupal\foo\Second']],
        ];
        yield 'parent' => [
            ['foo' => ['parent' => 'bar'], 'bar' => ['class' => 'Drupal\foo\First']],
            ['foo' => ['parent' => 'bar'], 'bar' => ['class' => 'Drupal\foo\Second']],
        ];
        yield 'decorator' => [
            ['foo' => ['class' => 'Drupal\foo\Foo'], 'bar' => ['class' => 'Drupal\foo\First', 'decorates' => 'foo']],
            ['foo' => ['class' => 'Drupal\foo\Foo'], 'bar' => ['class' => 'Drupal\foo\Second', 'decorates' => 'foo']],
        ];
        yield 'decorator of a decorator' => [
            [
                'foo' => ['class' => 'Drupal\foo\Foo'],
                'bar' => ['class' => 'Drupal\foo\Bar', 'decorates' => 'foo'],
                'baz' => ['class' => 'Drupal\foo\First', 'decorates' => 'bar'],
            ],
            [
                'foo' => ['class' => 'Drupal\foo\Foo'],
                'bar' => ['class' => 'Drupal\foo\Bar', 'decorates' => 'foo'],
                'baz' => ['class' => 'Drupal\foo\Second', 'decorates' => 'bar'],
            ],
        ];
    }

    public function testDecoratorCycleTerminates(): void
    {
        self::assertNotSame('missing', $this->serviceValue([
            'foo' => ['class' => 'Drupal\foo\Foo', 'decorates' => 'bar'],
            'bar' => ['class' => 'Drupal\foo\Bar', 'decorates' => 'foo'],
        ]));
    }

    private function createExtension(string $name, ?string $path = null): Extension
    {
        $path ??= "modules/$name";
        return new Extension(__DIR__ . '/../fixtures/drupal', 'module', "$path/$name.info.yml", "$name.module");
    }

    /**
     * @param list<Extension> $modules
     * @param array<string, Extension> $themes
     */
    private function extensionListValue(array $modules, array $themes = []): string
    {
        return (new ExtensionListResultCacheValueExtension($this->createSite([], $modules, $themes)))
            ->getValue(ExtensionListResultCacheValueExtension::KEY);
    }

    public function testExtensionListIgnoresOrder(): void
    {
        $a = $this->createExtension('a');
        $b = $this->createExtension('b');
        self::assertSame($this->extensionListValue([$a, $b]), $this->extensionListValue([$b, $a]));
    }

    public function testAddingExtensionChangesValue(): void
    {
        $a = $this->createExtension('a');
        self::assertNotSame($this->extensionListValue([$a]), $this->extensionListValue([$a, $this->createExtension('b')]));
    }

    public function testMovingExtensionChangesValue(): void
    {
        self::assertNotSame(
            $this->extensionListValue([$this->createExtension('foo', 'modules/foo')]),
            $this->extensionListValue([$this->createExtension('foo', 'modules/custom/foo')])
        );
    }

    public function testExtensionTypeChangesValue(): void
    {
        $extension = $this->createExtension('foo');
        self::assertNotSame($this->extensionListValue([$extension]), $this->extensionListValue([], ['foo' => $extension]));
    }

    /**
     * @param list<string> $directories
     */
    private function schemaValue(array $directories): string
    {
        return (new ConfigSchemaResultCacheValueExtension($this->createSite([], [], [], $directories)))
            ->getValue(ConfigSchemaResultCacheValueExtension::KEY);
    }

    public function testSchemaValue(): void
    {
        $directory = \sys_get_temp_dir() . '/phpstan_drupal_schema_' . \uniqid();
        \mkdir($directory);
        $file = $directory . '/test.schema.yml';

        try {
            $empty = $this->schemaValue([$directory]);

            \file_put_contents($file, "test.settings:\n  type: config_object\n");
            $created = $this->schemaValue([$directory]);
            self::assertNotSame($empty, $created);
            self::assertSame($created, $this->schemaValue([$directory]));

            \file_put_contents($file, "test.settings:\n  type: mapping\n");
            self::assertNotSame($created, $this->schemaValue([$directory]));
        } finally {
            @\unlink($file);
            \rmdir($directory);
        }
    }

    public function testMissingSchemaDirectoryIsStable(): void
    {
        self::assertSame($this->schemaValue(['/nonexistent/schema']), $this->schemaValue([]));
    }
}
