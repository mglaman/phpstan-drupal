<?php

// Test that ComputedItemListTrait usage does not produce incorrect
// "Parameter #1 $offset ... expects int, TKey given" errors.
// See https://github.com/mglaman/phpstan-drupal/pull/914

namespace Bug914;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * A computed field item list, like Drupal\content_moderation\Plugin\Field\ModerationStateFieldItemList.
 *
 * @extends FieldItemList<FieldItemInterface>
 */
class ComputedFieldItemList extends FieldItemList {

    use ComputedItemListTrait;

    protected function computeValue(): void {}

}

function test_computed_offset_exists(ComputedFieldItemList $list): bool {
    return $list->offsetExists(0);
}
