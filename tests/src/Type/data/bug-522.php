<?php

namespace Bug522;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\RoleInterface;
use function PHPStan\Testing\assertType;

class Foo {
    /**
     * The role storage used when changing the admin role.
     *
     * @var \Drupal\user\RoleStorageInterface
     */
    protected $roleStorage;

    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->roleStorage = $entityTypeManager->getStorage('user_role');
    }

    public function foo()
    {
        $roles = $this->roleStorage->loadMultiple();
        assertType('array<string, Drupal\user\Entity\Role>', $roles);
        unset($roles[RoleInterface::ANONYMOUS_ID]);
    }
}
