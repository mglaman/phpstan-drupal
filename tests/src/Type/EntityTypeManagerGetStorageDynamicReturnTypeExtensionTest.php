<?php

namespace mglaman\PHPStanDrupal\Tests\Type;

use Drupal\Core\Entity\EntityTypeManager;
use mglaman\PHPStanDrupal\Tests\AdditionalConfigFilesTrait;
use mglaman\PHPStanDrupal\Type\EntityTypeManagerGetStorageDynamicReturnTypeExtension;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPStan\Type\ObjectType;
use Prophecy\Prophet;

final class EntityTypeManagerGetStorageDynamicReturnTypeExtensionTest extends TypeInferenceTestCase
{
    use AdditionalConfigFilesTrait;

    public function dataFileAsserts(): iterable
    {
        yield from $this->gatherAssertTypes(__DIR__ . '/data/entity-type-manager.php');
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
