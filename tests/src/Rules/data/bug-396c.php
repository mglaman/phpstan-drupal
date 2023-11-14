<?php

namespace Bug396c;

use Drupal\Core\Entity\EntityStorageInterface;

class EntityQueryTest {
    private EntityStorageInterface $storage;
    public function setUp(): void {
        $this->storage = \Drupal::entityTypeManager()->getStorage('entity_test_mulrev');
    }
    public function a(): int {
        $query = $this->storage
            ->getQuery('OR')
            ->accessCheck(FALSE)
            ->exists('abcfoo', 'tr')
            ->condition("abd.color", 'red')
            ->sort('id');
        $count_query = clone $query;
        return $count_query->count()->execute();
    }
    public function b(): int {
        $query = $this->storage
            ->getQuery('OR')
            ->accessCheck(FALSE)
            ->exists('abcfoo', 'tr')
            ->condition("abd.color", 'red')
            ->sort('id');
        return $query->count()->execute();
    }
}
