<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityStorage;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use PHPStan\Type\ObjectType;

final class EntityStorageHelper
{
    public static function getTypeFromStorageObjectType(ObjectType $classType): ?EntityStorageType
    {
        if ((new ObjectType(ConfigEntityStorageInterface::class))->isSuperTypeOf($classType)->yes()) {
            $storageClassName = ConfigEntityStorage::class;
        } elseif ((new ObjectType(SqlEntityStorageInterface::class))->isSuperTypeOf($classType)->yes()) {
            $storageClassName = SqlContentEntityStorage::class;
        } else {
            return null;
        }

        $storageType = new ObjectType($storageClassName);
        if ((new ObjectType(EntityStorageInterface::class))->isSuperTypeOf($storageType)->no()) {
            return null;
        }
        if ((new ObjectType(ConfigEntityStorageInterface::class))->isSuperTypeOf($storageType)->yes()) {
            return new ConfigEntityStorageType('', $storageClassName);
        }
        if ((new ObjectType(ContentEntityStorageInterface::class))->isSuperTypeOf($storageType)->yes()) {
            return new ContentEntityStorageType('', $storageClassName);
        }
        return new EntityStorageType('', $storageClassName);
    }
}
