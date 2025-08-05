<?php

namespace EntityTypeStubs;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityType;
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

assert($entityTypeDefault instanceof EntityType);
assertType('string|false', $entityTypeDefault->getLinkTemplate('foo'));

assert($noLinkTemplate instanceof EntityType);
assert($noLinkTemplate->hasLinkTemplate('foo') === FALSE);
assertType('false', $noLinkTemplate->getLinkTemplate('foo'));

assert($hasLinkTemplate instanceof EntityType);
assert($hasLinkTemplate->hasLinkTemplate('foo') === TRUE);
assertType('string', $hasLinkTemplate->getLinkTemplate('foo'));

// Test getting a link template doesn't affect another link template.
assert($entityType instanceof EntityType);
assert($entityType->hasLinkTemplate('foo') === TRUE);
assertType('string', $entityType->getLinkTemplate('foo'));
// A different arg that wasn't narrowed previously:
assertType('string|false', $entityType->getLinkTemplate('bar'));
// ...until we know better:
assert($entityType->hasLinkTemplate('bar') === TRUE);
assertType('string', $entityType->getLinkTemplate('bar'));
