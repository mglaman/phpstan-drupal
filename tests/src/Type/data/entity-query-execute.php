<?php

namespace DrupalEntity;

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
