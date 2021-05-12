<?php

namespace PHPStan\Drupal;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\EntityTypeManagerGetStorageDynamicReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophet;

final class EntityTypeManagerGetStorageDynamicReturnTypeExtensionTest extends TestCase
{
    /**
     * @var Prophet
     *
     * @internal
     */
    private $prophet;

    protected function setUp(): void
    {
        parent::setUp();
        // @note we do not use phpspec/prophecy-phpunit due to conflicts with Drupal 8 PHPUnit.
        $this->prophet = new Prophet;
    }

    /**
     * @covers \PHPStan\Type\EntityTypeManagerGetStorageDynamicReturnTypeExtension::__construct
     * @covers \PHPStan\Type\EntityTypeManagerGetStorageDynamicReturnTypeExtension::getClass
     */
    public function testGetClass()
    {
        $x = new EntityTypeManagerGetStorageDynamicReturnTypeExtension([]);
        self::assertEquals('Drupal\Core\Entity\EntityTypeManagerInterface', $x->getClass());
    }

    /**
     * @dataProvider getEntityStorageProvider
     *
     * @covers \PHPStan\Type\EntityTypeManagerGetStorageDynamicReturnTypeExtension::__construct
     * @covers \PHPStan\Type\EntityTypeManagerGetStorageDynamicReturnTypeExtension::getTypeFromMethodCall
     */
    public function testGetTypeFromMethodCall($entityType, $storageClass)
    {
        // If we were passed a string, assume it is a class name to be mocked.
        if (is_string($entityType)) {
            $entityType = $this->prophet->prophesize($entityType)->reveal();
        }

        $x = new EntityTypeManagerGetStorageDynamicReturnTypeExtension([
            'node' => 'Drupal\node\NodeStorage',
            'search_api_index' => 'Drupal\search_api\Entity\SearchApiConfigEntityStorage',
        ]);

        $methodReflection = $this->prophet->prophesize(MethodReflection::class);
        $methodReflection->getName()->willReturn('getStorage');

        $defaultObjectType = $this->prophet->prophesize(ObjectType::class);
        $defaultObjectType->getClassName()->willReturn('Drupal\Core\Entity\EntityStorageInterface');
        $variantsParametersAcceptor = $this->prophet->prophesize(ParametersAcceptor::class);
        $variantsParametersAcceptor->getReturnType()->willReturn($defaultObjectType->reveal());
        $methodReflection->getVariants()->willReturn([$variantsParametersAcceptor->reveal()]);

        if ($entityType === null) {
            $this->expectException(ShouldNotHappenException::class);
            $methodCall = new MethodCall(
                $this->prophet->prophesize(Expr::class)->reveal(),
                'getStorage'
            );
        } else {
            $methodCall = new MethodCall(
                $this->prophet->prophesize(Expr::class)->reveal(),
                'getStorage',
                [new Arg($entityType)]
            );
        }

        $scope = $this->prophet->prophesize(Scope::class);

        $type = $x->getTypeFromMethodCall(
            $methodReflection->reveal(),
            $methodCall,
            $scope->reveal()
        );
        self::assertInstanceOf(ObjectType::class, $type);
        assert($type instanceof ObjectType);
        self::assertEquals($storageClass, $type->getClassName());
    }

    public function getEntityStorageProvider(): \Iterator
    {
        yield [new String_('node'), 'Drupal\node\NodeStorage'];
        yield [new String_('user'), 'Drupal\Core\Entity\EntityStorageInterface'];
        yield [new String_('search_api_index'), 'Drupal\search_api\Entity\SearchApiConfigEntityStorage'];
        yield [null, null];
        yield [MethodCall::class, 'Drupal\Core\Entity\EntityStorageInterface'];
        yield [Expr\StaticCall::class, 'Drupal\Core\Entity\EntityStorageInterface'];
        yield [Expr\BinaryOp\Concat::class, 'Drupal\Core\Entity\EntityStorageInterface'];
    }

    /**
     * @covers \PHPStan\Type\EntityTypeManagerGetStorageDynamicReturnTypeExtension::__construct
     * @covers \PHPStan\Type\EntityTypeManagerGetStorageDynamicReturnTypeExtension::isMethodSupported
     */
    public function testIsMethodSupported()
    {
        $x = new EntityTypeManagerGetStorageDynamicReturnTypeExtension([]);

        $valid = $this->prophet->prophesize(MethodReflection::class);
        $valid->getName()->willReturn('getStorage');
        self::assertTrue($x->isMethodSupported($valid->reveal()));

        $invalid = $this->prophet->prophesize(MethodReflection::class);
        $invalid->getName()->willReturn('getAccessControlHandler');
        self::assertFalse($x->isMethodSupported($invalid->reveal()));
    }
}
