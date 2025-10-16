<?php

namespace DrupalEntity;

use function PHPStan\Testing\assertType;

// Test case from issue #909: Entity query count() should return non-negative-int
// Currently this fails because PHPStan returns 'int' instead of 'int<0, max>'
assertType(
    'int<0, max>',
    \Drupal::entityTypeManager()
        ->getStorage('example')
        ->getQuery()
        ->accessCheck()
        ->count()
        ->execute()
);

// Additional test cases to ensure consistency across different entity query patterns
assertType(
    'int<0, max>',
    \Drupal::entityTypeManager()
        ->getStorage('node')
        ->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute()
);

assertType(
    'int<0, max>',
    \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->count()
        ->execute()
);

assertType(
    'int<0, max>',
    \Drupal::entityQuery('block')
        ->count()
        ->execute()
);

// Test with additional conditions - should still return non-negative-int
assertType(
    'int<0, max>',
    \Drupal::entityTypeManager()
        ->getStorage('node')
        ->getQuery()
        ->count()
        ->condition('status', 1)
        ->accessCheck(TRUE)
        ->execute()
);