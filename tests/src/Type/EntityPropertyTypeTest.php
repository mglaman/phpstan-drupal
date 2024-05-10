<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Type;

use mglaman\PHPStanDrupal\Tests\AdditionalConfigFilesTrait;
use PHPStan\Testing\TypeInferenceTestCase;

final class EntityPropertyTypeTest extends TypeInferenceTestCase
{
    use AdditionalConfigFilesTrait;

    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/entity-properties.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/book-module.php');
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
