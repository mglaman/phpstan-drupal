<?php

namespace Bug474;

class TestClass {

    /**
     * Tests entity queries using getAggregateQuery with access check.
     */
    function bug474(): void
    {
        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->accessCheck(FALSE)
            ->condition('field_test', 'foo', '=')
            ->execute();

        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->accessCheck(FALSE)
            ->condition('field_test', 'foo', '=')
            ->execute();

        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->condition('field_test', 'foo', '=')
            ->accessCheck(TRUE)
            ->execute();

        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->accessCheck(TRUE)
            ->condition('field_test', 'foo', '=')
            ->execute();

        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->condition('field_test', 'foo', '=')
            ->accessCheck()
            ->execute();

        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->accessCheck()
            ->condition('field_test', 'foo', '=')
            ->execute();

        // Failing test due to missing accessCheck.
        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->condition('field_test', 'foo', '=')
            ->execute();

        // Same tests but now with count() chained as well.
        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->accessCheck(FALSE)
            ->condition('field_test', 'foo', '=')
            ->count()
            ->execute();

        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->count()
            ->accessCheck(FALSE)
            ->condition('field_test', 'foo', '=')
            ->execute();

        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->condition('field_test', 'foo', '=')
            ->accessCheck(TRUE)
            ->count()
            ->execute();

        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->count()
            ->accessCheck(TRUE)
            ->condition('field_test', 'foo', '=')
            ->execute();

        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->condition('field_test', 'foo', '=')
            ->accessCheck()
            ->count()
            ->execute();

        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->count()
            ->accessCheck()
            ->condition('field_test', 'foo', '=')
            ->execute();

        // Failing test due to missing accessCheck.
        \Drupal::entityTypeManager()->getStorage('node')
            ->getAggregateQuery()
            ->condition('field_test', 'foo', '=')
            ->count()
            ->execute();
    }
}
