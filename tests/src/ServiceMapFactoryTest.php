<?php

namespace mglaman\PHPStanDrupal\Tests;

use Drupal\Core\Logger\LoggerChannel;
use mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PHPUnit\Framework\TestCase;

final class ServiceMapFactoryTest extends TestCase
{

    /**
     * @dataProvider getServiceProvider
     *
     * @covers \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition::__construct
     * @covers \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition::addDecorator
     * @covers \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition::getClass
     * @covers \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition::getDecorators
     * @covers \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition::isPublic
     * @covers \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition::getAlias
     * @covers \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition::getId
     * @covers \mglaman\PHPStanDrupal\Drupal\ServiceMap::setDrupalServices
     * @covers \mglaman\PHPStanDrupal\Drupal\ServiceMap::getService
     * @covers \mglaman\PHPStanDrupal\Drupal\ServiceMap::resolveParentDefinition
     */
    public function testFactory(string $id, array $serviceMap, callable $validator): void
    {
        $service = new ServiceMap();
        $service->setDrupalServices($serviceMap);
        $validator($service->getService($id));
    }

    public function getServiceProvider(): \Iterator
    {
        yield [
            'unknown',
            [],
            function (?DrupalServiceDefinition $service): void {
                self::assertNull($service, 'unknown');
            },
        ];
        yield [
            'entity_type.manager',
            [
                'entity_type.manager' => [
                    'class' => 'Drupal\Core\Entity\EntityTypeManager'
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                self::assertEquals('entity_type.manager', $service->getId());
                self::assertEquals('Drupal\Core\Entity\EntityTypeManager', $service->getClass());
                self::assertTrue($service->isPublic());
                self::assertNull($service->getAlias());
            }
        ];
        // For now factories are skipped.
        yield [
            'skipped_factory',
            [
                'skipped_factory' => [
                    'factory' => 'cache_factory:get',
                    'arguments' => ['cache'],
                ],
            ],
            function (?DrupalServiceDefinition $service): void {
                self::assertNull($service);
            },
        ];
        yield [
            'config.storage.sync',
            [
                'config.storage.staging' => [
                    'class' => 'Drupal\Core\Config\FileStorage',
                ],
                'config.storage.sync' => [
                    'alias' => 'config.storage.staging',
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                self::assertEquals('Drupal\Core\Config\FileStorage', $service->getClass());
            }
        ];
        yield [
            'concrete_service',
            [
                'abstract_service' => [
                    'abstract' => true,
                    'class' => 'Drupal\service_map\MyService',
                ],
                'concrete_service' => [
                    'parent' => 'abstract_service',
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                self::assertEquals('concrete_service', $service->getId());
                self::assertEquals('Drupal\service_map\MyService', $service->getClass());
                self::assertTrue($service->isPublic());
                self::assertNull($service->getAlias());
            }
        ];
        yield [
            'concrete_service_with_a_parent_which_has_a_parent',
            [
                'abstract_service' => [
                    'abstract' => true,
                    'class' => 'Drupal\service_map\MyService',
                ],
                'concrete_service' => [
                    'parent' => 'abstract_service',
                ],
                'concrete_service_with_a_parent_which_has_a_parent' => [
                    'parent' => 'concrete_service',
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                self::assertEquals('concrete_service_with_a_parent_which_has_a_parent', $service->getId());
                self::assertEquals('Drupal\service_map\MyService', $service->getClass());
                self::assertTrue($service->isPublic());
                self::assertNull($service->getAlias());
            }
        ];
        yield [
            'abstract_service_private',
            [
                'abstract_service_private' => [
                    'abstract' => true,
                    'class' => 'Drupal\service_map\MyService',
                    'public' => false,
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                self::assertEquals('abstract_service_private', $service->getId());
                self::assertEquals('Drupal\service_map\MyService', $service->getClass());
                self::assertFalse($service->isPublic());
                self::assertNull($service->getAlias());
            }
        ];
        yield [
            'concrete_service_overriding_definition_of_its_parent',
            [
                'abstract_service_private' => [
                    'abstract' => true,
                    'class' => 'Drupal\service_map\MyService',
                    'public' => false,
                ],
                'concrete_service_overriding_definition_of_its_parent' => [
                    'parent' => 'abstract_service_private',
                    'class' => 'Drupal\service_map\Override',
                    'public' => true,
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                self::assertEquals('concrete_service_overriding_definition_of_its_parent', $service->getId());
                self::assertEquals('Drupal\service_map\Override', $service->getClass());
                self::assertTrue($service->isPublic());
                self::assertNull($service->getAlias());
            }
        ];
        yield [
            'service_with_unknown_parent',
            [
                'service_with_unknown_parent' => [
                    'parent' => 'unknown_parent',
                ],
            ],
            function (?DrupalServiceDefinition $service): void {
                self::assertNull($service);
            }
        ];
        yield [
            'service_with_unknown_parent_overriding_definition_of_its_parent',
            [
                'service_with_unknown_parent' => [
                    'parent' => 'unknown_parent',
                ],
                'service_with_unknown_parent_overriding_definition_of_its_parent' => [
                    'parent' => 'unknown_parent',
                    'class' => 'Drupal\service_map\Override',
                    'public' => false,
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                self::assertEquals('service_with_unknown_parent_overriding_definition_of_its_parent', $service->getId());
                self::assertEquals('Drupal\service_map\Override', $service->getClass());
                self::assertFalse($service->isPublic());
                self::assertNull($service->getAlias());
            }
        ];
        yield [
            'service_map.base',
            [
                'service_map.base' => [
                    'class' => 'Drupal\service_map\Base',
                    'public' => false,
                    'abstract' => true,
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                self::assertEquals('service_map.base', $service->getId());
                self::assertEquals('Drupal\service_map\Base', $service->getClass());
                self::assertFalse($service->isPublic());
                self::assertNull($service->getAlias());
            }
        ];
        yield [
            'service_map.second_base',
            [
                'service_map.base' => [
                    'class' => 'Drupal\service_map\Base',
                    'public' => false,
                    'abstract' => true,
                ],
                'service_map.second_base' => [
                    'parent' => 'service_map.base',
                    'class' => 'Drupal\service_map\SecondBase',
                    'abstract' => true,
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                self::assertEquals('service_map.second_base', $service->getId());
                self::assertEquals('Drupal\service_map\SecondBase', $service->getClass());
                self::assertFalse($service->isPublic());
                self::assertNull($service->getAlias());
            }
        ];
        yield [
            'service_map.concrete_overriding_its_parent_which_has_a_parent',
            [
                'service_map.base' => [
                    'class' => 'Drupal\service_map\Base',
                    'public' => false,
                    'abstract' => true,
                ],
                'service_map.second_base' => [
                    'parent' => 'service_map.base',
                    'class' => 'Drupal\service_map\SecondBase',
                    'abstract' => true,
                ],
                'service_map.concrete_overriding_its_parent_which_has_a_parent' => [
                    'parent' => 'service_map.second_base',
                    'class' => 'Drupal\service_map\Concrete',
                    'public' => true,
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                self::assertEquals('service_map.concrete_overriding_its_parent_which_has_a_parent', $service->getId());
                self::assertEquals('Drupal\service_map\Concrete', $service->getClass());
                self::assertTrue($service->isPublic());
                self::assertNull($service->getAlias());
            }
        ];
        yield [
            'Psr\Log\LoggerInterface $loggerWorkspaces',
            [
                'logger.channel_base' => [
                    'abstract' => true,
                    'class' => LoggerChannel::class,
                    'factory' => ['@logger.factory', 'get'],
                ],
                'logger.channel.workspaces' => [
                    'parent' => 'logger.channel_base',
                    'arguments' => ['workspaces'],
                ],
                'Psr\Log\LoggerInterface $loggerWorkspaces' => [
                    'alias' => 'logger.channel.workspaces'
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                self::assertEquals(LoggerChannel::class, $service->getClass());
            }
        ];
        yield [
            'service_map.base_to_be_decorated',
            [
                'service_map.deocrating_base' => [
                    'decorates' => 'service_map.base_to_be_decorated',
                    'class' => 'Drupal\service_map\SecondBase',
                ],
                'service_map.decorates_decorating_base' => [
                    'decorates' => 'service_map.deocrating_base',
                    'class' => 'Drupal\service_map\Override',
                ],
                'service_map.base_to_be_decorated' => [
                    'class' => 'Drupal\service_map\Base',
                    'abstract' => true,
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                $decorators = $service->getDecorators();
                self::assertCount(1, $decorators);
                self::assertArrayHasKey('service_map.deocrating_base', $decorators);
                $child_decorators = $decorators['service_map.deocrating_base']->getDecorators();
                self::assertCount(1, $child_decorators);
                self::assertArrayHasKey('service_map.decorates_decorating_base', $child_decorators);
            }
        ];
        yield [
            'Drupal\phpstan_example\Foo',
            [
                'Drupal\phpstan_example\Foo' => null,
                'Drupal\phpstan_example\BarInterface' => '@Drupal\phpstan_example\Bar',
                'Drupal\phpstan_example\Bar' => [
                    'decorates' => 'Drupal\phpstan_example\Foo'
                ],
            ],
            function (DrupalServiceDefinition $service): void {
                self::assertCount(2, $service->getDecorators());
            }
        ];
    }

}
