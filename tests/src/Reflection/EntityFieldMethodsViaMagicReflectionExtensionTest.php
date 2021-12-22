<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Reflection;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\node\Entity\Node;
use mglaman\PHPStanDrupal\Reflection\EntityFieldMethodsViaMagicReflectionExtension;
use mglaman\PHPStanDrupal\Tests\AdditionalConfigFilesTrait;
use PHPStan\Testing\PHPStanTestCase;

final class EntityFieldMethodsViaMagicReflectionExtensionTest extends PHPStanTestCase {

    use AdditionalConfigFilesTrait;

    /**
     * @var \mglaman\PHPStanDrupal\Reflection\EntityFieldMethodsViaMagicReflectionExtension
     */
    private $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new EntityFieldMethodsViaMagicReflectionExtension();
    }

    /**
     * @dataProvider dataHasMethod
     *
     * @param string $method
     * @param bool $result
     */
    public function testHasMethod(string $class, string $method, bool $result): void
    {
        $reflection = $this->createReflectionProvider()->getClass($class);
        self::assertEquals($result, $this->extension->hasMethod($reflection, $method));
    }

    public function dataHasMethod(): \Generator
    {
        // Technically it does not have this method. But we allow it for now.
        yield 'field item list: referencedEntities' => [
            FieldItemListInterface::class,
            'referencedEntities',
            true,
        ];

        // A content entity for sure does not have this method.
        yield 'Content entity: referencedEntities' => [
            // @phpstan-ignore-next-line
            EntityTest::class,
            'referencedEntities',
            false,
        ];
    }

}
