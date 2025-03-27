<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Reflection;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\module_installer_config_test\Entity\TestConfigType;
use Drupal\phpstan_fixtures\Entity\ReflectionEntityTest;
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
        $this->extension = new EntityFieldsViaMagicReflectionExtension(self::createReflectionProvider());
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

    public static function dataHasProperty(): \Generator
    {
        yield 'content entity supported' => [
            // @phpstan-ignore class.notFound
            EntityTest::class,
            'foobar',
            true
        ];
        yield 'config entity not supported' => [
            // @phpstan-ignore class.notFound
            TestConfigType::class,
            'foobar',
            false
        ];
        yield 'annotated properties are skipped on content entities' => [
            // @phpstan-ignore class.notFound
            ReflectionEntityTest::class,
            'user_id',
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
        yield 'field item list: value (read from interface stub)' => [
            \Drupal\Core\Field\FieldItemList::class,
            'value',
            false,
        ];
        yield 'field item list_interface: value' => [
            \Drupal\Core\Field\FieldItemListInterface::class,
            'value',
            false,
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
        // @phpstan-ignore class.notFound
        $classReflection = $this->createReflectionProvider()->getClass(EntityTest::class);
        $propertyReflection = $this->extension->getProperty($classReflection, 'field_myfield');
        $readableType = $propertyReflection->getReadableType();
        self::assertInstanceOf(ObjectType::class, $readableType);
        self::assertEquals(FieldItemListInterface::class, $readableType->getClassName());
        $writeableType = $propertyReflection->getWritableType();
        self::assertInstanceOf(ObjectType::class, $writeableType);
        self::assertEquals(FieldItemListInterface::class, $writeableType->getClassName());

        $originalPropertyReflection = $this->extension->getProperty($classReflection, 'original');
        $readableType = $originalPropertyReflection->getReadableType();
        self::assertInstanceOf(ObjectType::class, $readableType);
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
