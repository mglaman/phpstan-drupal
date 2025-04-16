<?php

namespace DrupalFormatterBaseGeneric;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use function PHPStan\Testing\assertType;

/**
 * Provides a custom field item list with concrete FieldItemListInterface<T>.
 *
 * @extends FieldItemList<BooleanItem>
 */
class BooleanFieldItemList extends FieldItemList {}

/**
 * @extends FormatterBase<BooleanFieldItemList>
 */
class ExtendsBooleanItemFormatter extends FormatterBase {

    public function prepareView(array $entities_items): void {
        assertType('array<DrupalFormatterBaseGeneric\BooleanFieldItemList>', $entities_items);
        $items = $entities_items[0];
        assertType('DrupalFormatterBaseGeneric\BooleanFieldItemList', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem', $item);
        }
    }

    public function view(FieldItemListInterface $items, $langcode = NULL) {
        assertType('DrupalFormatterBaseGeneric\BooleanFieldItemList', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem', $item);
        }
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        assertType('DrupalFormatterBaseGeneric\BooleanFieldItemList', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem', $item);
        }
    }

}

/**
 * @extends FormatterBase<FieldItemList<StringItem>>
 */
class ExtendedDeepFormatter extends FormatterBase {

    public function prepareView(array $entities_items): void {
        assertType('array<Drupal\Core\Field\FieldItemList<Drupal\Core\Field\Plugin\Field\FieldType\StringItem>>', $entities_items);
        $items = $entities_items[0];
        assertType('Drupal\Core\Field\FieldItemList<Drupal\Core\Field\Plugin\Field\FieldType\StringItem>', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem', $item);
        }
    }

    public function view(FieldItemListInterface $items, $langcode = NULL) {
        assertType('Drupal\Core\Field\FieldItemList<Drupal\Core\Field\Plugin\Field\FieldType\StringItem>', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem', $item);
        }
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        assertType('Drupal\Core\Field\FieldItemList<Drupal\Core\Field\Plugin\Field\FieldType\StringItem>', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem', $item);
        }
    }

}
