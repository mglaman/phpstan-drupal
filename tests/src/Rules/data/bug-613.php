<?php

namespace Bug613;

function access_check_first(): array {
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $query
        ->accessCheck(FALSE)
        ->condition('field_myfield', TRUE);
    return $query->execute();
}

function access_check_last(): array {
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $query
        ->condition('field_myfield', TRUE)
        ->accessCheck(FALSE);
    return $query->execute();
}

function access_check_with_condition_group(): array {
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $query
        ->accessCheck(FALSE)
        ->condition($query->andConditionGroup()
            ->condition('status', 1)
            ->condition('promote', 1));
    return $query->execute();
}

function access_check_then_several_conditions(): array {
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $query->accessCheck(FALSE)
        ->condition('type', 'article')
        ->condition('status', 1)
        ->condition('promote', 1);
    return $query->execute();
}

function no_access_check(): array {
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $query
        ->condition('type', 'article')
        ->condition('status', 1);
    return $query->execute();
}
