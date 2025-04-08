<?php

namespace DrupalFormatterInterface;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use function PHPStan\Testing\assertType;

class EmptyFormatter implements FormatterInterface {

    public function prepareView(array $entities_items): void {
        assertType('array<Drupal\Core\Field\FieldItemListInterface>', $entities_items);
        $items = $entities_items[0];
        assertType('Drupal\Core\Field\FieldItemListInterface', $items);
        assertType('Drupal\Core\TypedData\TypedDataInterface|null', $items->first());
        assertType('Drupal\Core\TypedData\TypedDataInterface|null', $items->get(0));
        assertType('Drupal\Core\TypedData\TypedDataInterface|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('mixed', $item);
        }
    }

    public function view(FieldItemListInterface $items, $langcode = NULL) {
        assertType('Drupal\Core\Field\FieldItemListInterface', $items);
        assertType('Drupal\Core\TypedData\TypedDataInterface|null', $items->first());
        assertType('Drupal\Core\TypedData\TypedDataInterface|null', $items->get(0));
        assertType('Drupal\Core\TypedData\TypedDataInterface|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('mixed', $item);
        }
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        assertType('Drupal\Core\Field\FieldItemListInterface', $items);
        assertType('Drupal\Core\TypedData\TypedDataInterface|null', $items->first());
        assertType('Drupal\Core\TypedData\TypedDataInterface|null', $items->get(0));
        assertType('Drupal\Core\TypedData\TypedDataInterface|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('mixed', $item);
        }
    }

}

/**
 * @implements FormatterInterface<FieldItemList>
 */
class FieldItemListFormatter implements FormatterInterface {

    public function prepareView(array $entities_items): void {
        assertType('array<Drupal\Core\Field\FieldItemList>', $entities_items);
        $items = $entities_items[0];
        assertType('Drupal\Core\Field\FieldItemList', $items);
        assertType('Drupal\Core\Field\FieldItemInterface|null', $items->first());
        assertType('Drupal\Core\Field\FieldItemInterface|null', $items->get(0));
        assertType('Drupal\Core\Field\FieldItemInterface|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\FieldItemInterface', $item);
        }
    }

    public function view(FieldItemListInterface $items, $langcode = NULL) {
        assertType('Drupal\Core\Field\FieldItemList', $items);
        assertType('Drupal\Core\Field\FieldItemInterface|null', $items->first());
        assertType('Drupal\Core\Field\FieldItemInterface|null', $items->get(0));
        assertType('Drupal\Core\Field\FieldItemInterface|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\FieldItemInterface', $item);
        }
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        assertType('Drupal\Core\Field\FieldItemList', $items);
        assertType('Drupal\Core\Field\FieldItemInterface|null', $items->first());
        assertType('Drupal\Core\Field\FieldItemInterface|null', $items->get(0));
        assertType('Drupal\Core\Field\FieldItemInterface|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\FieldItemInterface', $item);
        }
    }

}

/**
 * @implements FormatterInterface<FieldItemList<StringItem>>
 */
class StringItemFormatter implements FormatterInterface {

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

/**
 * @implements FormatterInterface<FieldItemList<StringItem|BooleanItem>>
 */
class UnionTypeFormatter implements FormatterInterface {

    public function prepareView(array $entities_items): void {
        assertType('array<Drupal\Core\Field\FieldItemList<Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem>>', $entities_items);
        $items = $entities_items[0];
        assertType('Drupal\Core\Field\FieldItemList<Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem>', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem', $item);
        }
    }

    public function view(FieldItemListInterface $items, $langcode = NULL) {
        assertType('Drupal\Core\Field\FieldItemList<Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem>', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem', $item);
        }
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        assertType('Drupal\Core\Field\FieldItemList<Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem>', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|Drupal\Core\Field\Plugin\Field\FieldType\StringItem', $item);
        }
    }

}

/**
 * @implements FieldItemListInterface<BooleanItem>
 */
class FakeBooleanFieldItemList implements FieldItemListInterface {
}

/**
 * @implements FormatterInterface<FakeBooleanFieldItemList>
 */
class BooleanItemFormatter implements FormatterInterface {

    public function prepareView(array $entities_items): void {
        assertType('array<DrupalFormatterInterface\FakeBooleanFieldItemList>', $entities_items);
        $items = $entities_items[0];
        assertType('DrupalFormatterInterface\FakeBooleanFieldItemList', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem', $item);
        }
    }

    public function view(FieldItemListInterface $items, $langcode = NULL) {
        assertType('DrupalFormatterInterface\FakeBooleanFieldItemList', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem', $item);
        }
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        assertType('DrupalFormatterInterface\FakeBooleanFieldItemList', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem', $item);
        }
    }

}

/**
 * @extends FormatterBase<FakeBooleanFieldItemList>
 */
class ExtendsBooleanItemFormatter extends FormatterBase {

    public function prepareView(array $entities_items): void {
        assertType('array<DrupalFormatterInterface\FakeBooleanFieldItemList>', $entities_items);
        $items = $entities_items[0];
        assertType('DrupalFormatterInterface\FakeBooleanFieldItemList', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem', $item);
        }
    }

    public function view(FieldItemListInterface $items, $langcode = NULL) {
        assertType('DrupalFormatterInterface\FakeBooleanFieldItemList', $items);
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->first());
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->get(0));
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem|null', $items->offsetGet(0));
        foreach ($items as $item) {
            assertType('Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem', $item);
        }
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        assertType('DrupalFormatterInterface\FakeBooleanFieldItemList', $items);
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
