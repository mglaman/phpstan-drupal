<?php

namespace DrupalEntity;

use Drupal\node\NodeStorage;
use function PHPStan\Testing\assertType;

/** @var \Drupal\node\NodeStorage $typedNodeStorage */
$typedNodeStorage = \Drupal::entityTypeManager()->getStorage('node');
assertType(
    'array<int, string>',
    $typedNodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->execute()
);
$query = $typedNodeStorage->getQuery()
    ->accessCheck(TRUE);
assertType('array<int, string>', $query->execute());
assertType(
    'int',
    $typedNodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute()
);
$query = $typedNodeStorage->getQuery()
    ->accessCheck(TRUE)
    ->count();
assertType('int', $query->execute());

/** @var \Drupal\node\NodeStorageInterface $anotherTypedNodeStorage */
$anotherTypedNodeStorage = \Drupal::entityTypeManager()->getStorage('node');
assertType(
    'array<int, string>',
    $anotherTypedNodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->execute()
);
$query = $anotherTypedNodeStorage->getQuery()
    ->accessCheck(TRUE);
assertType('array<int, string>', $query->execute());
assertType(
    'int',
    $anotherTypedNodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute()
);
$query = $anotherTypedNodeStorage->getQuery()
    ->accessCheck(TRUE)
    ->count();
assertType('int', $query->execute());

$instanceOfNodeStorage = \Drupal::entityTypeManager()->getStorage('node');
if ($instanceOfNodeStorage instanceof NodeStorage) {
    assertType(
        'array<int, string>',
        $instanceOfNodeStorage->getQuery()
            ->accessCheck(TRUE)
            ->execute()
    );
    $query = $instanceOfNodeStorage->getQuery()
        ->accessCheck(TRUE);
    assertType('array<int, string>', $query->execute());
    assertType(
        'int',
        $instanceOfNodeStorage->getQuery()
            ->accessCheck(TRUE)
            ->count()
            ->execute()
    );
    $query = $instanceOfNodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->count();
    assertType('int', $query->execute());
}
