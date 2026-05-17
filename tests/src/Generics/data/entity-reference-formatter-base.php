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
    }

    public function view(FieldItemListInterface $items, $langcode = NULL) {
        assertType('Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\Core\Entity\EntityInterface>', $items);
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        assertType('Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\Core\Entity\EntityInterface>', $items);
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
    }

    public function view(FieldItemListInterface $items, $langcode = NULL) {
        assertType('Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\node\NodeInterface>', $items);
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        assertType('Drupal\Core\Field\EntityReferenceFieldItemList<Drupal\node\NodeInterface>', $items);
    }

}
