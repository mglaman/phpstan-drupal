<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules\data;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

// Error: non-promoted constructor param typed EntityStorageInterface.
class ServiceWithStorageInjected
{
    public function __construct(
        private EntityTypeManagerInterface $entityTypeManager,
        EntityStorageInterface $nodeStorage // error on this line
    ) {
    }
}

// Error: promoted constructor param typed EntityStorageInterface.
class ServiceWithPromotedStorageInjected
{
    public function __construct(
        private EntityStorageInterface $storage // error on this line
    ) {
    }
}

// Error: storage param alongside other valid params.
class ServiceWithMixedParams
{
    public function __construct(
        private EntityTypeManagerInterface $entityTypeManager,
        private EntityStorageInterface $storage // error on this line
    ) {
    }
}

// Error: nullable EntityStorageInterface constructor param.
class ServiceWithNullableStorageInjected
{
    public function __construct(
        private ?EntityStorageInterface $storage // error on this line
    ) {
    }
}

// No error: correct pattern — inject EntityTypeManagerInterface.
class ServiceWithEntityTypeManager
{
    public function __construct(
        private EntityTypeManagerInterface $entityTypeManager
    ) {
    }
}

// No error: EntityStorageInterface param in a non-constructor method.
class ServiceWithStorageInOtherMethod
{
    public function doSomething(EntityStorageInterface $storage): void
    {
    }
}

// No error: constructor with no type hints.
class ServiceWithUntypedParam
{
    public function __construct($storage)
    {
    }
}
