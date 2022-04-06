<?php

namespace bug377;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeStorageInterface;
use function PHPStan\Testing\assertType;

class Foo {
    private EntityTypeManagerInterface $entityTypeManager;
    private EntityStorageInterface $myEntityStorage;
    private ConfigEntityStorageInterface $configEntityStorage;
    private ContentEntityStorageInterface $contentEntityStorage;
    private NodeStorageInterface $nodeStorage;

    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
        $this->myEntityStorage = $entityTypeManager->getStorage('node');
        $this->configEntityStorage = $entityTypeManager->getStorage('block');
        $this->contentEntityStorage = $entityTypeManager->getStorage('node');
        $this->nodeStorage = $entityTypeManager->getStorage('node');
    }

    public function storageType() {
        // @todo property typing overrides our internal typing to determine entity storage type.
        assertType(EntityStorageInterface::class, $this->myEntityStorage);
        assertType('Drupal\node\NodeStorage', $this->entityTypeManager->getStorage('node'));
        assertType(ConfigEntityStorageInterface::class, $this->configEntityStorage);
        assertType(ContentEntityStorageInterface::class, $this->contentEntityStorage);
        assertType(NodeStorageInterface::class, $this->nodeStorage);
    }

    public function entityType() {
        $entity = $this->myEntityStorage->load('123');
        assertType('Drupal\Core\Entity\EntityInterface|null', $entity);
        $entity = $this->configEntityStorage->load('123');
        // @todo this can safely say it is ConfigEntityInterface as return type.
        assertType('Drupal\Core\Entity\EntityInterface|null', $entity);
        $entity = $this->contentEntityStorage->load('123');
        // @todo this can safely say it is ConfigEntityInterface as return type.
        assertType('Drupal\Core\Entity\EntityInterface|null', $entity);
        $entity = $this->nodeStorage->load('123');
        assertType('Drupal\node\Entity\Node|null', $entity);
    }

}
