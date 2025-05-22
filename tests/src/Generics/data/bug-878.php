<?php

namespace Bug878;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\node\Entity\Node;
use function PHPStan\Testing\assertType;

class EntityReferenceRevisionsFieldItemList extends EntityReferenceFieldItemList implements EntityReferenceFieldItemListInterface {

    /**
     * {@inheritdoc}
     */
    public function referencedEntities() {
        $target_entities = [];
        foreach ($this->list as $delta => $item) {
            if ($item->entity) {
                $target_entities[$delta] = $item->entity;
            }
        }
        return $target_entities;
    }


}

$paragraphFields = [];

$node = Node::load(1);
assert($node instanceof Node);

foreach ($node as $field) {
    if ($field instanceof EntityReferenceRevisionsFieldItemList) {
        $paragraphFields[] = $field;
    }
}

foreach ($paragraphFields as $field) {
    foreach ($field->referencedEntities() as $entity) {
        assertType('Drupal\Core\Entity\EntityInterface', $entity);
        assertType('int|string|null', $entity->id());
    }
}
