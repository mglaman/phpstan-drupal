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

/**
 * Test that the protected $list property preserves generic type information.
 */
class ListPropertyAccessor extends EntityReferenceFieldItemList {
    public function testListProperty(): void {
        // Direct access to $list property should preserve generic type
        assertType('array<int, Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>>', $this->list);
        
        // Iteration over $list should preserve generic type
        foreach ($this->list as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>', $item);
        }
        
        // Array access with positive integer offset
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>', $this->list[0]);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\Core\Entity\EntityInterface>', $this->list[5]);
    }
}

/**
 * Test with specialized generic type.
 */
class ListPropertyAccessorWithGeneric extends EntityReferenceFieldItemList {
    /**
     * @var array<int, EntityReferenceItem<NodeInterface>>
     */
    protected $list = [];

    public function testSpecializedListProperty(): void {
        // Access to specialized $list property
        assertType('array<int, Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>>', $this->list);
        
        // Iteration preserves specialized generic type
        foreach ($this->list as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>', $item);
        }
        
        // Item access preserves specialized generic type
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>', $this->list[0]);
    }
}

/**
 * @extends EntityReferenceFieldItemList<NodeInterface>
 */
class ExtendedListPropertyAccessor extends EntityReferenceFieldItemList {
    public function testExtendedListProperty(): void {
        // Access to $list property should use extended generic type
        assertType('array<int, Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>>', $this->list);
        
        // Iteration preserves extended generic type
        foreach ($this->list as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>', $item);
        }
        
        // Array access with extended generic type
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem<Drupal\node\NodeInterface>', $this->list[0]);
    }
}
