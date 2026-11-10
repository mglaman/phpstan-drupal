<?php

namespace Bug825;

// Fluent: accessCheck() immediately after entityQuery().
function accessCheckImmediatelyAfterEntityQuery(): void {
    \Drupal::entityQuery('node')
        ->accessCheck()
        ->execute();
}

// Fluent: accessCheck() immediately after entityQuery(), then condition.
function accessCheckBeforeCondition(): void {
    \Drupal::entityQuery('node')
        ->accessCheck()
        ->condition('type', 'article')
        ->execute();
}

// Fluent: condition before accessCheck().
function conditionBeforeAccessCheck(): void {
    \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->accessCheck()
        ->execute();
}

// Non-fluent: separate statements.
function separateStatements(): void {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'article');
    $query->accessCheck();
    $query->execute();
}
