<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules\data;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

// Error: getStorage() result stored as class property in constructor.
class ServiceStoringStorageFromManagerInConstructor
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

// Error: getStorage() result stored as class property in a non-constructor method.
class ServiceStoringStorageInSetter
{
    private EntityStorageInterface $storage;

    public function __construct(private EntityTypeManagerInterface $entityTypeManager)
    {
    }

    public function setStorage(): void
    {
        $this->storage = $this->entityTypeManager->getStorage('node'); // error on this line
    }
}

// Error: storage fetched into local variable then stored as property.
class ServiceStoringStorageViaLocalVariable
{
    private $storage;

    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $storage = $entityTypeManager->getStorage('node');
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

// No error: getStorage() used via local variable at the call-site.
class ServiceCallingStorageAtCallSite
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

// No error: assigning null to a nullable non-storage property (issue #983).
class ServiceWithNullableNonStorageProperty
{
    protected ?int $foo = null;

    public function reset(): void
    {
        $this->foo = null;
    }
}
