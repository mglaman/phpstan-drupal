<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Type;

use mglaman\PHPStanDrupal\Tests\AdditionalConfigFilesTrait;
use PHPStan\Testing\TypeInferenceTestCase;

final class AccessibleReturnTypeExtensionTest extends TypeInferenceTestCase {

    use AdditionalConfigFilesTrait;

    public function dataFileAsserts(): iterable
    {
        yield from $this->gatherAssertTypes(__DIR__ . '/data/accessible.php');
        yield from $this->gatherAssertTypes(__DIR__ . '/data/bug-414.php');
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
