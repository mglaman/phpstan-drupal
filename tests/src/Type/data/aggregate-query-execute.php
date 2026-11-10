<?php

declare(strict_types=1);

namespace DrupalAggregateQuery;

use function PHPStan\Testing\assertType;

// An aggregate query grouped by a field returns a list of result rows, where
// each row is an array keyed by the aggregate results and the groupBy columns.
// @see \Drupal\Core\Entity\Query\QueryAggregateInterface::execute()
$rows = \Drupal::entityTypeManager()->getStorage('node')->getAggregateQuery()
    ->accessCheck(FALSE)
    ->condition('status', TRUE)
    ->groupBy('type')
    ->execute();
assertType('list<array<string, string|null>>', $rows);

// array_column() over those rows pulls a single groupBy column back out, which
// is the pattern used to collect the grouped IDs for a follow-up query.
$ids = array_column(
    \Drupal::entityTypeManager()->getStorage('node')->getAggregateQuery()
        ->accessCheck(FALSE)
        ->condition('status', TRUE)
        ->groupBy('type')
        ->execute(),
    'type'
);
assertType('list<string|null>', $ids);

// Because the column can be present, the result is not statically empty, so a
// truthiness check on it is meaningful rather than "always false".
$filtered = $ids ? $ids : [];
assertType('list<string|null>', $filtered);

$query = \Drupal::entityTypeManager()->getStorage('node')->getAggregateQuery();
$query->accessCheck(TRUE);
$query->groupBy('type');
assertType('list<array<string, string|null>>', $query->execute());

assertType(
    'array<int, array<string, string|null>>',
    \Drupal::entityTypeManager()->getStorage('node')->getAggregateQuery()->groupBy('type')->execute()
);

assertType(
    'int<0, max>',
    \Drupal::entityTypeManager()->getStorage('node')->getAggregateQuery()->accessCheck(TRUE)->count()->execute()
);

assertType(
    'list<array<string, string|null>>',
    \Drupal::entityQueryAggregate('node')->accessCheck(TRUE)->groupBy('type')->execute()
);
