<?php

declare(strict_types=1);

final class EntityQueryAccessCheckInvalid
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
