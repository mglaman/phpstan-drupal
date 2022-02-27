<?php

namespace DrupalEntity;

use Drupal\node\NodeStorage;
use function PHPStan\Testing\assertType;
assertType(
    'array<int, string>',
    \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->execute()
);
assertType(
    'array<int, string>',
    \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->accessCheck(TRUE)
        ->execute()
);
assertType(
    'int',
    \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute()
);
assertType(
    'int',
    \Drupal::entityTypeManager()->getStorage('node')->getQuery()
        ->count()
        ->condition('foo', 'bar')
        ->accessCheck(TRUE)
        ->execute()
);
assertType(
    'int',
    \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->count()
        ->execute()
);
assertType(
    'array<int, string>',
    \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->execute()
);

$query = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
    ->accessCheck(TRUE);
assertType('array<int, string>', $query->execute());
$query = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
    ->accessCheck(TRUE)->count();
assertType('int', $query->execute());

$nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
assertType(
    'array<int, string>',
    $nodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->execute()
);
$query = $nodeStorage->getQuery()
    ->accessCheck(TRUE);
assertType('array<int, string>', $query->execute());
assertType(
    'int',
    $nodeStorage->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute()
);
$query = $nodeStorage->getQuery()
    ->accessCheck(TRUE)
    ->count();
assertType('int', $query->execute());

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

$anotherTypedNodeStorage = \Drupal::entityTypeManager()->getStorage('node');
if ($anotherTypedNodeStorage instanceof NodeStorage) {
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
}

assertType(
    'array<string, string>',
    \Drupal::entityTypeManager()->getStorage('block')->getQuery()
        ->execute()
);
assertType(
    'array<string, string>',
    \Drupal::entityTypeManager()->getStorage('block')->getQuery()
        ->accessCheck(TRUE)
        ->execute()
);
assertType(
    'int',
    \Drupal::entityTypeManager()->getStorage('block')->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute()
);
assertType(
    'int',
    \Drupal::entityQuery('block')
        ->accessCheck(TRUE)
        ->count()
        ->execute()
);
assertType(
    'array<string, string>',
    \Drupal::entityQuery('block')
        ->accessCheck(TRUE)
        ->execute()
);

$query = \Drupal::entityTypeManager()->getStorage('block')->getQuery()
    ->accessCheck(TRUE);
assertType('array<string, string>', $query->execute());
$query = \Drupal::entityTypeManager()->getStorage('block')->getQuery()
    ->accessCheck(TRUE)->count();
assertType('int', $query->execute());
