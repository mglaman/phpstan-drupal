<?php

namespace Bug530;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Tests entity count queries with access check.
 */
class TestClass {

    public ConfigEntityStorageInterface $storage;

    public function setUp(): void {
        /** @var ConfigEntityStorageInterface  $this->storage */
        $this->storage = \Drupal::entityTypeManager()->getStorage('menu');
    }

    public function bug530(string $entity_type): void {
        // Test "normal" entity query with class property.
        $this->storage->getQuery()
            ->condition('field_test', 'foo', '=')
            ->execute();

        // Test "normal" entity query with inline type hint.
        /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
        $storage = \Drupal::entityTypeManager()->getStorage('menu');
        $count = $this->storage->getQuery()
            ->condition('field_test', 'foo', '=')
            ->execute();

        // Test count entity query with class property.
        $this->storage->getQuery()
            ->condition('field_test', 'foo', '=')
            ->count()
            ->execute();

        // Test count entity query with inline type hint.
        /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
        $storage = \Drupal::entityTypeManager()->getStorage('menu');
        $count = $this->storage->getQuery()
            ->condition('field_test', 'foo', '=')
            ->count()
            ->execute();
    }
}
