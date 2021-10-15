<?php

namespace mglaman\PHPStanDrupal\Drupal;

use PHPUnit\Framework\TestCase;

final class ServiceMapFactoryTest extends TestCase
{

    /**
     * @dataProvider getServiceProvider
     *
     * @covers \PHPStan\Drupal\DrupalServiceDefinition::__construct
     * @covers \PHPStan\Drupal\DrupalServiceDefinition::getClass
     * @covers \PHPStan\Drupal\DrupalServiceDefinition::isPublic
     * @covers \PHPStan\Drupal\DrupalServiceDefinition::getAlias
     * @covers \PHPStan\Drupal\DrupalServiceDefinition::getId
     * @covers \PHPStan\Drupal\ServiceMap::setDrupalServices
     * @covers \PHPStan\Drupal\ServiceMap::getService
     */
    public function testFactory(string $id, callable $validator)
    {
        $service = new ServiceMap();
        $service->setDrupalServices([
            'entity_type.manager' => [
                'class' => 'Drupal\Core\Entity\EntityTypeManager'
            ],
            'skipped_factory' => [
                'factory' => 'cache_factory:get',
                'arguments' => ['cache'],
            ],
            'config.storage.staging' => [
                'class' => 'Drupal\Core\Config\FileStorage',
            ],
            'config.storage.sync' => [
                'alias' => 'config.storage.staging',
            ]
        ]);
        $validator($service->getService($id));
    }

    public function getServiceProvider(): \Iterator
    {
        yield [
            'unknown',
            function (?DrupalServiceDefinition $service): void {
                self::assertNull($service, 'unknown');
            },
        ];
        yield [
            'entity_type.manager',
            function (?DrupalServiceDefinition $service): void {
                self::assertNotNull($service);
                self::assertEquals('entity_type.manager', $service->getId());
                self::assertEquals('Drupal\Core\Entity\EntityTypeManager', $service->getClass());
                self::assertTrue($service->isPublic());
                self::assertNull($service->getAlias());
            }
        ];
        // For now factories are skipped.
        yield [
            'skipped_factory',
            function (?DrupalServiceDefinition $service): void {
                self::assertNull($service);
            },
        ];
        yield [
            'config.storage.sync',
            function (?DrupalServiceDefinition $service): void {
                self::assertNotNull($service);
                self::assertEquals('config.storage.staging', $service->getAlias());
            }
        ];
    }

}
