<?php

declare(strict_types=1);

namespace fixtures\drupal\modules\phpstan_fixtures\src;

final class EntityQueryWithoutAccessRule
{
    public function foo(): void
    {
        \Drupal::entityTypeManager()->getStorage('node')
            ->getQuery()
            ->condition('field_test', 'foo', '=')
            ->execute();
    }

    public function bar(): void
    {
        \Drupal::entityQuery('node')
            ->condition('field_test', 'foo', '=')
            ->execute();
    }

}
