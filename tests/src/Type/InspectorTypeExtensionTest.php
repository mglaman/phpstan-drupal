<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Type;

use mglaman\PHPStanDrupal\Tests\AdditionalConfigFilesTrait;
use PHPStan\Testing\TypeInferenceTestCase;

final class InspectorTypeExtensionTest extends TypeInferenceTestCase {

  use AdditionalConfigFilesTrait;

  public static function dataFileAsserts(): iterable {
    yield from self::gatherAssertTypes(__DIR__ . '/data/inspector.php');
    yield from self::gatherAssertTypes(__DIR__ . '/data/bug-852.php');
    yield from self::gatherAssertTypes(__DIR__ . '/data/bug-857.php');
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
  ): void {
    $this->assertFileAsserts($assertType, $file, ...$args);
  }

}
