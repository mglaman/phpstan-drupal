<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Type;

use Drupal\Core\Entity\EntityTypeManager;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use mglaman\PHPStanDrupal\Drupal\ServiceMapStaticAccessor;
use mglaman\PHPStanDrupal\Tests\AdditionalConfigFilesTrait;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

final class ServiceIdTypeTest extends PHPStanTestCase {

    use AdditionalConfigFilesTrait;

    public function dataAccepts(): iterable
    {
        yield 'self' => [
            new \mglaman\PHPStanDrupal\Type\ServiceIdType(),
            new \mglaman\PHPStanDrupal\Type\ServiceIdType(),
            TrinaryLogic::createYes(),
        ];
        yield 'valid service' => [
            new \mglaman\PHPStanDrupal\Type\ServiceIdType(),
            new ConstantStringType('entity_type.manager'),
            TrinaryLogic::createYes(),
        ];
        yield 'invalid service' => [
            new \mglaman\PHPStanDrupal\Type\ServiceIdType(),
            new ConstantStringType('foo.manager'),
            TrinaryLogic::createMaybe(),
        ];
        yield 'generic string' => [
            new \mglaman\PHPStanDrupal\Type\ServiceIdType(),
            new StringType(),
            TrinaryLogic::createMaybe(),
        ];
    }

    /**
     * @dataProvider dataAccepts
     */
    public function testAccepts(\mglaman\PHPStanDrupal\Type\ServiceIdType $type, Type $otherType, TrinaryLogic $expectedResult): void
    {
        $serviceMap = new ServiceMap();
        $serviceMap->setDrupalServices([
            'entity_type.manager' => [
                'class' => EntityTypeManager::class
            ],
        ]);
        ServiceMapStaticAccessor::registerInstance($serviceMap);
        $actualResult = $type->accepts($otherType, true);
        self::assertSame(
            $expectedResult->describe(),
            $actualResult->describe(),
            sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise())),
        );
    }

}
