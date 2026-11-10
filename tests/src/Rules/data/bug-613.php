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

final class QueryHolder
{
    private \Drupal\Core\Entity\Query\QueryInterface $query;

    private static \Drupal\Core\Entity\Query\QueryInterface $staticQuery;

    public function __construct()
    {
        $this->query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
        self::$staticQuery = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    }

    public function property(): array
    {
        $this->query
            ->condition('status', 1)
            ->accessCheck(FALSE);
        return $this->query->execute();
    }

    public function staticProperty(): array
    {
        self::$staticQuery
            ->condition('status', 1)
            ->accessCheck(FALSE);
        return self::$staticQuery->execute();
    }
}

function array_element(): array {
    $queries = ['node' => \Drupal::entityTypeManager()->getStorage('node')->getQuery()];
    $queries['node']->accessCheck(FALSE);
    return $queries['node']->execute();
}

function nullsafe_link(): array {
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $query?->condition('status', 1)->accessCheck(FALSE);
    return $query->execute();
}

function count_chain(): int {
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $query->accessCheck(FALSE)->count();
    return $query->execute();
}
