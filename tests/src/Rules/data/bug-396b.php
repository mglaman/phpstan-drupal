<?php

namespace Bug396b;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class Foo {
    private EntityTypeManagerInterface $entityTypeManager;
    private string $entityTypeId;
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityTypeId = 'node';
    }
    public function a(): int {
        /** @var \Drupal\Core\Entity\TranslatableRevisionableStorageInterface|\Drupal\Core\Entity\EntityStorageInterface $storage */
        $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
        return $storage->getQuery()->accessCheck(FALSE)->count()->execute() + 1;
    }
}
