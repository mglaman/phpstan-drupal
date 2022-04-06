<?php

namespace bug377;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use function PHPStan\Testing\assertType;

class Foo {
    private EntityTypeManagerInterface $entityTypeManager;
    private EntityStorageInterface $myEntityStorage;

    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
        $this->myEntityStorage = $entityTypeManager->getStorage('node');
    }

    public function storageType() {
        // @todo property typing overrides our internal typing to determine entity storage type.
        assertType('Drupal\Core\Entity\EntityStorageInterface', $this->myEntityStorage);
        assertType('Drupal\node\NodeStorage', $this->entityTypeManager->getStorage('node'));
    }

    public function entityType() {
        $entity = $this->myEntityStorage->load('123');
        assertType('Drupal\Core\Entity\EntityInterface|null', $entity);
    }

}
