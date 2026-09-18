<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules\data;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

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

// No error: EntityListBuilder::__construct() requires $storage, so a subclass
// adding a dependency has to accept and forward it (issue #1039).
class ListBuilderWithExtraDependency extends EntityListBuilder
{
    public function __construct(
        EntityTypeInterface $entity_type,
        EntityStorageInterface $storage,
        private DateFormatterInterface $dateFormatter
    ) {
        parent::__construct($entity_type, $storage);
    }
}

// No error: same for DraggableListBuilder, the reproducer from issue #1039.
abstract class GroupRoleListBuilder extends DraggableListBuilder
{
    protected RouteMatchInterface $routeMatch;

    public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RouteMatchInterface $route_match)
    {
        parent::__construct($entity_type, $storage);
        $this->routeMatch = $route_match;
    }
}

// No error: the subclass does not have to reuse the parent's parameter name;
// the rule matches on type, not on name.
class ListBuilderWithRenamedStorageParam extends EntityListBuilder
{
    public function __construct(EntityTypeInterface $entityType, EntityStorageInterface $entityStorage)
    {
        parent::__construct($entityType, $entityStorage);
    }
}

// No error: the obligation is inherited through an intermediate class that
// does not override the constructor.
class ListBuilderWithoutConstructor extends EntityListBuilder
{
}
class ListBuilderInheritingThroughIntermediate extends ListBuilderWithoutConstructor
{
    public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, private DateFormatterInterface $dateFormatter)
    {
        parent::__construct($entity_type, $storage);
    }
}

// Error: the parent constructor does not require storage, so injecting it is
// the subclass's own choice.
class ServiceParent
{
    public function __construct(protected EntityTypeManagerInterface $entityTypeManager)
    {
    }
}
class ServiceChildWithStorageInjected extends ServiceParent
{
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        private EntityStorageInterface $storage // error on this line
    ) {
        parent::__construct($entityTypeManager);
    }
}

// Error: the parent requires one storage handler; a second one is the
// subclass's own addition and is reported.
class ListBuilderWithSecondStorageInjected extends EntityListBuilder
{
    public function __construct(
        EntityTypeInterface $entity_type,
        EntityStorageInterface $storage,
        private EntityStorageInterface $userStorage // error on this line
    ) {
        parent::__construct($entity_type, $storage);
    }
}

// Error: the nearest ancestor constructor dropped the storage parameter, so
// re-introducing it is a choice, not an obligation.
class ListBuilderWithoutStorageParam extends EntityListBuilder
{
    public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entityTypeManager)
    {
        parent::__construct($entity_type, $entityTypeManager->getStorage($entity_type->id()));
    }
}
class ListBuilderReintroducingStorageParam extends ListBuilderWithoutStorageParam
{
    public function __construct(
        EntityTypeInterface $entity_type,
        EntityTypeManagerInterface $entityTypeManager,
        EntityStorageInterface $storage // error on this line
    ) {
        parent::__construct($entity_type, $entityTypeManager);
    }
}
