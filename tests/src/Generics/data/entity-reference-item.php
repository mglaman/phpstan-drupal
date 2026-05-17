<?php

namespace DrupalEntityReferenceItemGeneric;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\node\NodeInterface;
use function PHPStan\Testing\assertType;

function defaultItem(EntityReferenceItem $item): void {
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem', $item);
    assertType('Drupal\Core\Entity\EntityInterface|null', $item->entity);
    assertType('int|string|null', $item->target_id);
}

/**
 * @param EntityReferenceItem<NodeInterface> $item
 */
function phpDocOverride(EntityReferenceItem $item): void {
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>', $item);
    assertType('Drupal\node\NodeInterface|null', $item->entity);
    assertType('int|string|null', $item->target_id);
}

/**
 * @extends EntityReferenceItem<NodeInterface>
 */
class ExtendedEntityReferenceItem extends EntityReferenceItem {}

function extendedItem(ExtendedEntityReferenceItem $item): void {
    assertType('DrupalEntityReferenceItemGeneric\ExtendedEntityReferenceItem', $item);
    assertType('Drupal\node\NodeInterface|null', $item->entity);
    assertType('int|string|null', $item->target_id);
}
