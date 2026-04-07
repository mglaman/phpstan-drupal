<?php

namespace DrupalEntity;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldItemList;
use Drupal\node\Entity\Node;
use Drupal\phpstan_fixtures\Entity\ReflectionEntityTest;
use function PHPStan\Testing\assertType;

$node = Node::create(['type' => 'page']);
assertType('Drupal\Core\Field\FieldItemListInterface', $node->uid);

$entity = ReflectionEntityTest::create();
assertType('Drupal\Core\Field\EntityReferenceFieldItemListInterface', $entity->user_id);
assertType('Drupal\Core\Field\EntityReferenceFieldItemListInterface<Drupal\entity_test\Entity\EntityTest>', $entity->related);
// Value of array is derived from 'related' generic.
assertType('array<int, Drupal\entity_test\Entity\EntityTest>', $entity->related->referencedEntities());

// FieldItemList magic property delegation via __get.
/** @var FieldItemList $fieldItemList */
// Known entity reference properties have specific types via extension.
assertType('Drupal\Core\Entity\EntityInterface|null', $fieldItemList->entity);
assertType('string', $fieldItemList->target_id);
// 'value' is mixed because FieldItemListInterface declares @property mixed $value.
assertType('mixed', $fieldItemList->value);
// Previously unsupported properties now return mixed via extension (not null).
assertType('mixed', $fieldItemList->format);
assertType('mixed', $fieldItemList->processed);
assertType('mixed', $fieldItemList->uri);

// Stub-declared @property types are not overridden by the extension.
// EntityReferenceFieldItemListInterface declares target_id and entity via @property.
/** @var EntityReferenceFieldItemList $refList */
assertType('int|string|null', $refList->target_id);
assertType('Drupal\Core\Entity\EntityInterface|null', $refList->entity);
