<?php

/**
 * Cache key differs outside of class so this is separate reproduction
 *
 * @return void
 */
function bug475Caching(): void
{
    // Here condition() return type will be cached as one that has no access check
    \Drupal::entityQuery('node')
        ->condition('field_test', 'foo', '=')
        ->accessCheck(FALSE)
        ->execute();

    // Cache return on condition() will also be no access check, even though caller did, unless caller type changes cache key
    \Drupal::entityQuery('node')
        ->accessCheck(FALSE)
        ->condition('field_test', 'foo', '=')
        ->execute();
}
