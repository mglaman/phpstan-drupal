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

    public function count(): void
    {
        \Drupal::entityTypeManager()->getStorage('node')
            ->getQuery()
            ->accessCheck(false)
            ->count()
            ->execute();
    }

    public function bug381CountOrderShouldNotMatter(): void
    {
        $query = \Drupal::entityTypeManager()->getStorage('node')
            ->getQuery()
            ->accessCheck(FALSE)
            ->count();
        $count = (int) $query->execute();

        $query = \Drupal::entityTypeManager()->getStorage('node')
            ->getQuery()
            ->count()
            ->accessCheck(FALSE);
        $count = (int) $query->execute();
    }
}
