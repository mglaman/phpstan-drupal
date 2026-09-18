<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Type;

use PHPStan\Testing\TypeInferenceTestCase;
use Symfony\Component\Yaml\Yaml;
use function array_key_exists;
use function file_exists;

final class ConfigGetReturnTypeExtensionTest extends TypeInferenceTestCase
{

    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/../../fixtures/config/phpunit-drupal-phpstan-config-get-return-type.neon',
        ]);
    }

    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/config-get.php');
        if (self::systemMailIsFullyValidatable()) {
            yield from self::gatherAssertTypes(__DIR__ . '/data/config-get-system-mail.php');
        }
    }

    /**
     * system.mail is only FullyValidatable in newer Drupal core versions, so
     * gate the asserts that depend on it on the actual schema content.
     */
    private static function systemMailIsFullyValidatable(): bool
    {
        $schemaFile = __DIR__ . '/../../fixtures/drupal/core/modules/system/config/schema/system.schema.yml';
        if (!file_exists($schemaFile)) {
            return false;
        }
        $schema = Yaml::parseFile($schemaFile);
        // The constraint's value is null (`FullyValidatable: ~`), so isset()
        // would report false — use array_key_exists().
        return array_key_exists('FullyValidatable', $schema['system.mail']['constraints'] ?? []);
    }

    /**
     * @dataProvider dataFileAsserts
     * @param string $assertType
     * @param string $file
     * @param mixed ...$args
     */
    public function testFileAsserts(
        string $assertType,
        string $file,
        ...$args
    ): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }
}
