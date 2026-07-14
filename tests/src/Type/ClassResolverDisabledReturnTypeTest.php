<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Type;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use PHPStan\Testing\TypeInferenceTestCase;
use ReflectionMethod;
use function str_contains;

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
        if (!self::coreDeclaresClassResolverGenerics()) {
            yield from self::gatherAssertTypes(__DIR__ . '/data/drupal-class-resolver-disabled-legacy-core.php');
        }
    }

    /**
     * Drupal 11.x HEAD declares a conditional return type on
     * getInstanceFromDefinition(), so class-string arguments narrow from the
     * signature itself and the plain `object` fallback asserts only hold on
     * cores without the generics.
     */
    private static function coreDeclaresClassResolverGenerics(): bool
    {
        $method = new ReflectionMethod(ClassResolverInterface::class, 'getInstanceFromDefinition');
        return str_contains((string) $method->getDocComment(), '@template');
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
