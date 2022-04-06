<?php

declare(strict_types=1);

namespace fixtures\drupal\modules\phpstan_fixtures\src;

final class EntityQueryWithAccessRule
{
    public function foo(): void
    {
        \Drupal::entityTypeManager()->getStorage('node')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('field_test', 'foo', '=')
            ->execute();
    }

    public function bar(): void
    {
        \Drupal::entityQuery('node')
            ->accessCheck(FALSE)
            ->condition('field_test', 'foo', '=')
            ->execute();
    }
}
