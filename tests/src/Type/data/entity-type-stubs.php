<?php

namespace EntityTypeStubs;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\node\Entity\Node;

use function PHPStan\Testing\assertType;

function (ContentEntityInterface $entity): void
{
    foreach ($entity as $name => $field) {
        assertType('string', $name);
        assertType(FieldItemListInterface::class, $field);
    }
};
