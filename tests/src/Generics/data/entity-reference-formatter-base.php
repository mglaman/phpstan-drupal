<?php

namespace DrupalEntityReferenceFormatterBaseGeneric;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\node\NodeInterface;
use function PHPStan\Testing\assertType;

class InheritedEntityReferenceFormatterBase extends EntityReferenceFormatterBase {

    public function prepareView(array $entities_items): void {
        assertType('array<Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\Core\Entity\EntityInterface>>', $entities_items);
        $items = $entities_items[0];
        assertType('Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\Core\Entity\EntityInterface>', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>', $item);
            assertType('Drupal\Core\Entity\EntityInterface|null', $item->entity);
            assertType('int|string|null', $item->target_id);
        }
        assertType('array<int, Drupal\Core\Entity\EntityInterface>', $items->referencedEntities());
        foreach ($items->referencedEntities() as $entity) {
            assertType('Drupal\Core\Entity\EntityInterface', $entity);
        }
    }

    public function view(FieldItemListInterface $items, $langcode = NULL) {
        assertType('Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\Core\Entity\EntityInterface>', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>', $item);
            assertType('Drupal\Core\Entity\EntityInterface|null', $item->entity);
            assertType('int|string|null', $item->target_id);
        }
        assertType('array<int, Drupal\Core\Entity\EntityInterface>', $items->referencedEntities());
        foreach ($items->referencedEntities() as $entity) {
            assertType('Drupal\Core\Entity\EntityInterface', $entity);
        }
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        assertType('Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\Core\Entity\EntityInterface>', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>', $item);
            assertType('Drupal\Core\Entity\EntityInterface|null', $item->entity);
            assertType('int|string|null', $item->target_id);
        }
        assertType('array<int, Drupal\Core\Entity\EntityInterface>', $items->referencedEntities());
        foreach ($items->referencedEntities() as $entity) {
            assertType('Drupal\Core\Entity\EntityInterface', $entity);
        }
    }

}

/**
 * @extends EntityReferenceFormatterBase<NodeInterface>
 */
class SpecifiedEntityTypeEntityReferenceFormatterBase extends EntityReferenceFormatterBase {

    public function prepareView(array $entities_items): void {
        assertType('array<Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\node\NodeInterface>>', $entities_items);
        $items = $entities_items[0];
        assertType('Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\node\NodeInterface>', $items);
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

    public function view(FieldItemListInterface $items, $langcode = NULL) {
        assertType('Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\node\NodeInterface>', $items);
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

    public function viewElements(FieldItemListInterface $items, $langcode) {
        assertType('Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\node\NodeInterface>', $items);
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

}
