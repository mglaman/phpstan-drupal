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
    }
}
