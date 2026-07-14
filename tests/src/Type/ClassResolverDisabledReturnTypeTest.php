<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Type;

use PHPStan\Testing\TypeInferenceTestCase;

final class ClassResolverDisabledReturnTypeTest extends TypeInferenceTestCase
{

    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(parent::getAdditionalConfigFiles(), [
            __DIR__ . '/../../fixtures/config/phpunit-drupal-phpstan-class-resolver-disabled.neon',
        ]);
    }

    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/drupal-class-resolver-disabled.php');
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
