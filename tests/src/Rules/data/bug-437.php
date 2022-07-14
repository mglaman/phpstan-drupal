<?php

namespace Bug437;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;

final class Foo {
    private QueryInterface $vocabularyQuery;
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->vocabularyQuery = $entityTypeManager
            ->getStorage('taxonomy_term')
            ->getQuery()
            ->accessCheck(TRUE);
    }
    public function a(): int {
        return $this->vocabularyQuery->count()->execute();
    }
}
