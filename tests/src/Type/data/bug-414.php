<?php

namespace Bug414;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use function PHPStan\Testing\assertType;

function (EntityInterface $entity): void {
    assertType('bool', $entity->access('view'));
    assertType(AccessResultInterface::class, $entity->access('view', null, true));
};

function (EntityInterface $entity): void {
    $accessArgs = ['delete'];
    $access = $entity->access(...$accessArgs);
    assertType('bool', $access);

    $accessArgs = ['delete', null, false];
    $access = $entity->access(...$accessArgs);
    assertType('bool', $access);

    // @todo test after https://github.com/phpstan/phpstan/issues/7369
    // $accessArgs = ['delete', null, true];
    // $access = $entity->access(...$accessArgs);
    // assertType(AccessResultInterface::class, $access);
};

function (EntityInterface $entity): void {
    $access = $entity->access('delete', return_as_object: true);
    assertType(AccessResultInterface::class, $access);
    $access = $entity->access('delete', return_as_object: false);
    assertType('bool', $access);
};
