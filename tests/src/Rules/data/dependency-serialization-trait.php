<?php

namespace DependencySerialization;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;

class Foo {
    private EntityTypeManagerInterface $entityTypeManager;
}

class Bar {
    use DependencySerializationTrait;

    private EntityTypeManagerInterface $entityTypeManager;
}

class Baz {
    use DependencySerializationTrait;

    private readonly EntityTypeManagerInterface $entityTypeManager;
}

class Qux {
    use DependencySerializationTrait;

    protected readonly EntityTypeManagerInterface $entityTypeManager;
}

class FooForm extends FormBase {
    private EntityTypeManagerInterface $entityTypeManager;
}

class ReadonlyChildForm extends FormBase {
    public function __construct(
        protected readonly EntityTypeManagerInterface $entityTypeManager,
        protected readonly string $formName,
    ) {}
}

class ReadonlyDirectUse {
    use DependencySerializationTrait;

    public function __construct(
        protected readonly EntityTypeManagerInterface $entityTypeManager,
    ) {}
}
