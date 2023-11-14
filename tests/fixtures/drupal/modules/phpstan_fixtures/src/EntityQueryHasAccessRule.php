<?php

declare(strict_types=1);

namespace fixtures\drupal\modules\phpstan_fixtures\src;

use function PHPStan\dumpType;

final class EntityQueryHasAccessRule
{
    public function foo(): void
    {
        \Drupal::entityTypeManager()->getStorage('node')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('field_test', 'foo', '=')
            ->execute();
    }
}
