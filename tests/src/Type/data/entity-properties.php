<?php

namespace DrupalEntity;

use Drupal\node\Entity\Node;
use Drupal\phpstan_fixtures\Entity\ReflectionEntityTest;
use function PHPStan\Testing\assertType;

$node = Node::create(['type' => 'page']);
assertType('Drupal\Core\Field\FieldItemListInterface', $node->uid);

$entity = ReflectionEntityTest::create();
assertType('Drupal\Core\Field\EntityReferenceFieldItemListInterface', $entity->user_id);
