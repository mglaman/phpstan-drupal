<?php

namespace Bug494Example;

class TestClass {

    public function bug494($entity_type): void
    {
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
