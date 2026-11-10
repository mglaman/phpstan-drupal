<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Drupal;

use mglaman\PHPStanDrupal\Drupal\EntityDataRepository;
use PHPUnit\Framework\TestCase;

final class EntityDataRepositoryTest extends TestCase
{

    public function testGetUnknownEntityTypeIdIsNotRemembered(): void
    {
        $repository = new EntityDataRepository([
            'node' => ['class' => 'Drupal\node\Entity\Node'],
        ]);

        self::assertNull($repository->get('nodee')->getClassType());
        self::assertSame(['node'], $repository->getAllEntityTypeIds());
    }

    public function testGetKnownEntityTypeId(): void
    {
        $repository = new EntityDataRepository([
            'node' => ['class' => 'Drupal\node\Entity\Node'],
        ]);

        self::assertSame('Drupal\node\Entity\Node', $repository->get('node')->getClassType()?->getClassName());
        self::assertNull($repository->get('nodee')->getClassType());
    }

    public function testEmptyMapping(): void
    {
        self::assertSame([], (new EntityDataRepository([]))->getAllEntityTypeIds());
    }
}
