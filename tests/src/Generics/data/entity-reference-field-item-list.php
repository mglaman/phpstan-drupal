<?php

namespace DrupalEntityReferenceFieldItemListGeneric;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\node\NodeInterface;
use function PHPStan\Testing\assertType;

function defaultItemList(EntityReferenceFieldItemList $items): void {
    assertType('Drupal\Core\Field\EntityReferenceFieldItemList', $items);
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>|null', $items->first());
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>|null', $items->get(0));
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>|null', $items->offsetGet(0));
    foreach ($items as $item) {
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>', $item);
    }
    assertType('array<int, Drupal\Core\Entity\EntityInterface>', $items->referencedEntities());
    foreach ($items->referencedEntities() as $entity) {
        assertType('Drupal\Core\Entity\EntityInterface', $entity);
    }
}

/**
 * @param EntityReferenceFieldItemList<NodeInterface> $items
 */
function phpDocOverride(EntityReferenceFieldItemList $items): void {
    assertType('Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\node\NodeInterface>', $items);
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>|null', $items->first());
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>|null', $items->get(0));
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>|null', $items->offsetGet(0));
    foreach ($items as $item) {
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>', $item);
    }
    assertType('array<int, Drupal\node\NodeInterface>', $items->referencedEntities());
    foreach ($items->referencedEntities() as $entity) {
        assertType('Drupal\node\NodeInterface', $entity);
    }
}

/**
 * @extends EntityReferenceFieldItemList<NodeInterface>
 */
class ExtendedEntityReferenceFieldItemList extends EntityReferenceFieldItemList {}

function extendedItemList(ExtendedEntityReferenceFieldItemList $items): void {
    assertType('DrupalEntityReferenceFieldItemListGeneric\ExtendedEntityReferenceFieldItemList', $items);
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>|null', $items->first());
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>|null', $items->get(0));
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>|null', $items->offsetGet(0));
    foreach ($items as $item) {
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>', $item);
        assertType('Drupal\node\NodeInterface|null', $item->entity);
        assertType('int|string|null', $item->target_id);
    }
    assertType('array<int, Drupal\node\NodeInterface>', $items->referencedEntities());
    foreach ($items->referencedEntities() as $entity) {
        assertType('Drupal\node\NodeInterface', $entity);
    }
}
