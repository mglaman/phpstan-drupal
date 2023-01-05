<?php

namespace Bug494Example;

class TestClass {

    /**
     * Tests entity queries with access check and unknown entity type.
     */
    public function bug494(string $entity_type): void
    {
        \Drupal::entityQuery($entity_type)
            ->accessCheck(FALSE)
            ->condition('field_test', 'foo', '=')
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->condition('field_test', 'foo', '=')
            ->accessCheck(FALSE)
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->accessCheck(TRUE)
            ->condition('field_test', 'foo', '=')
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->condition('field_test', 'foo', '=')
            ->accessCheck(TRUE)
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->accessCheck()
            ->condition('field_test', 'foo', '=')
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->condition('field_test', 'foo', '=')
            ->accessCheck()
            ->execute();

        // Failing test due to missing accessCheck.
        \Drupal::entityQuery($entity_type)
            ->condition('field_test', 'foo', '=')
            ->execute();

        // Same tests but now with count() chained as well.
        \Drupal::entityQuery($entity_type)
            ->accessCheck(FALSE)
            ->condition('field_test', 'foo', '=')
            ->count()
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->condition('field_test', 'foo', '=')
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->condition('field_test', 'foo', '=')
            ->count()
            ->accessCheck(FALSE)
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->accessCheck(TRUE)
            ->condition('field_test', 'foo', '=')
            ->count()
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->condition('field_test', 'foo', '=')
            ->accessCheck(TRUE)
            ->count()
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->condition('field_test', 'foo', '=')
            ->count()
            ->accessCheck(TRUE)
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->accessCheck()
            ->condition('field_test', 'foo', '=')
            ->count()
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->condition('field_test', 'foo', '=')
            ->accessCheck()
            ->count()
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->condition('field_test', 'foo', '=')
            ->count()
            ->accessCheck()
            ->execute();

        // Failing tests due to missing accessCheck.
        \Drupal::entityQuery($entity_type)
            ->condition('field_test', 'foo', '=')
            ->count()
            ->execute();

        \Drupal::entityQuery($entity_type)
            ->count()
            ->condition('field_test', 'foo', '=')
            ->execute();
    }
}
