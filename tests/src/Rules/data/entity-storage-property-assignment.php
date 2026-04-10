<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules\data;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

// Error: getStorage() result stored as class property in constructor.
class ServiceStoringStorageFromManager
{
    private EntityStorageInterface $storage;

    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->storage = $entityTypeManager->getStorage('node'); // error on this line
    }
}

// Error: injected EntityStorageInterface param stored as class property.
class ServiceStoringInjectedStorage
{
    private $storage;

    public function __construct(EntityStorageInterface $storage)
    {
        $this->storage = $storage; // error on this line
    }
}

// No error: storing EntityTypeManagerInterface itself is fine.
class ServiceStoringEntityTypeManager
{
    private EntityTypeManagerInterface $entityTypeManager;

    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }
}

// No error: getStorage() called outside constructor (e.g. in a method).
class ServiceCallingStorageInMethod
{
    public function __construct(private EntityTypeManagerInterface $entityTypeManager)
    {
    }

    public function doSomething(): void
    {
        $storage = $this->entityTypeManager->getStorage('node');
        $storage->load(1);
    }
}

// No error: local variable assignment (not a property).
class ServiceWithLocalStorageVariable
{
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $storage = $entityTypeManager->getStorage('node');
    }
}
