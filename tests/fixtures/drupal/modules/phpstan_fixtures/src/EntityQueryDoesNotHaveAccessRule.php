<?php

declare(strict_types=1);

namespace fixtures\drupal\modules\phpstan_fixtures\src;

use function PHPStan\dumpType;

final class EntityQueryDoesNotHaveAccessRule
{
    public function foo(): void
    {
        \Drupal::entityTypeManager()->getStorage('node')
            ->getQuery()
            ->condition('field_test', 'foo', '=')
            ->execute();
    }
}
