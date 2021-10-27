<?php

namespace mglaman\PHPStanDrupal\Tests;

use mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use PHPUnit\Framework\TestCase;

final class ServiceMapFactoryTest extends TestCase
{

    /**
     * @dataProvider getServiceProvider
     *
     * @covers \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition::__construct
     * @covers \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition::getClass
     * @covers \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition::isPublic
     * @covers \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition::getAlias
     * @covers \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition::getId
     * @covers \mglaman\PHPStanDrupal\Drupal\ServiceMap::setDrupalServices
     * @covers \mglaman\PHPStanDrupal\Drupal\ServiceMap::getService
     */
    public function testFactory(string $id, callable $validator): void
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
            function (?DrupalServiceDefinition $service): void {
                self::assertNull($service);
            },
        ];
        yield [
            'config.storage.sync',
            function (DrupalServiceDefinition $service): void {
                self::assertEquals('config.storage.staging', $service->getAlias());
            }
        ];
    }

}
