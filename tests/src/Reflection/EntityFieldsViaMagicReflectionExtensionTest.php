<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Reflection;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\module_installer_config_test\Entity\TestConfigType;
use mglaman\PHPStanDrupal\Reflection\EntityFieldsViaMagicReflectionExtension;
use mglaman\PHPStanDrupal\Tests\AdditionalConfigFilesTrait;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;

final class EntityFieldsViaMagicReflectionExtensionTest extends PHPStanTestCase {

    use AdditionalConfigFilesTrait;

    /**
     * @var EntityFieldsViaMagicReflectionExtension
     */
    private $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new EntityFieldsViaMagicReflectionExtension();
    }

    /**
     * @dataProvider dataHasProperty
     * @param string $property
     * @param bool $result
     */
    public function testHasProperty(string $class, string $property, bool $result): void
    {
        $reflection = $this->createReflectionProvider()->getClass($class);
        self::assertEquals($result, $this->extension->hasProperty($reflection, $property));
    }

    public function dataHasProperty(): \Generator
    {
        yield 'content entity supported' => [
            // @phpstan-ignore-next-line
            EntityTest::class,
            'foobar',
            true
        ];
        yield 'config entity not supported' => [
            // @phpstan-ignore-next-line
            TestConfigType::class,
            'foobar',
            false
        ];
        // @todo really entity is only supported on EntityFieldItemList
        yield 'field item list: entity' => [
            \Drupal\Core\Field\FieldItemList::class,
            'entity',
            true,
        ];
        // @todo really entity is only supported on EntityFieldItemList
        yield 'field item list: target_id' => [
            \Drupal\Core\Field\FieldItemList::class,
            'target_id',
            true,
        ];
        yield 'field item list: value' => [
            \Drupal\Core\Field\FieldItemList::class,
            'target_id',
            true,
        ];
        // @todo support more proeprties.
        yield 'field item list: format' => [
            \Drupal\Core\Field\FieldItemList::class,
            'format',
            false,
        ];
    }

    public function testGetPropertyEntity(): void
    {
        // @phpstan-ignore-next-line
        $classReflection = $this->createReflectionProvider()->getClass(EntityTest::class);
        $propertyReflection = $this->extension->getProperty($classReflection, 'field_myfield');
        $readableType = $propertyReflection->getReadableType();
        assert($readableType instanceof ObjectType);
        self::assertEquals(FieldItemListInterface::class, $readableType->getClassName());
        $writeableType = $propertyReflection->getWritableType();
        assert($writeableType instanceof ObjectType);
        self::assertEquals(FieldItemListInterface::class, $writeableType->getClassName());

        $originalPropertyReflection = $this->extension->getProperty($classReflection, 'original');
        $readableType = $originalPropertyReflection->getReadableType();
        assert($readableType instanceof ObjectType);
        self::assertEquals(ContentEntityInterface::class, $readableType->getClassName());
    }

    public function testGetPropertyFieldItemList(): void
    {
        $classReflection = $this->createReflectionProvider()->getClass(\Drupal\Core\Field\FieldItemList::class);
        $propertyReflection = $this->extension->getProperty($classReflection, 'value');
        $readableType = $propertyReflection->getReadableType();
        self::assertInstanceOf(StringType::class, $readableType);
        $propertyReflection = $this->extension->getProperty($classReflection, 'target_id');
        $readableType = $propertyReflection->getReadableType();
        self::assertInstanceOf(StringType::class, $readableType);
        $propertyReflection = $this->extension->getProperty($classReflection, 'entity');
        $readableType = $propertyReflection->getReadableType();
        self::assertInstanceOf(ObjectType::class, $readableType);
        $propertyReflection = $this->extension->getProperty($classReflection, 'format');
        $readableType = $propertyReflection->getReadableType();
        self::assertInstanceOf(NullType::class, $readableType);

    }

}
