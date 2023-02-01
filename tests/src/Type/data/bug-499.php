<?php

namespace Bug499;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use function PHPStan\Testing\assertType;

class ComputedFieldItemList extends FieldItemList {
    use ComputedItemListTrait;

    protected function computeValue()
    {
        $item = $this->createItem(0, 'foo');
        assertType('Drupal\Core\Field\FieldItemInterface', $item);
        $this->list[0] = $item;
    }
}
