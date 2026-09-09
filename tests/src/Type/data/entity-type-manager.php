<?php

namespace EntityTypeManagerGetStorage;

use function PHPStan\Testing\assertType;

$etm = \Drupal::entityTypeManager();

assertType('Drupal\node\NodeStorage', $etm->getStorage('node'));
assertType('Drupal\user\UserStorage', $etm->getStorage('user'));
assertType('Drupal\taxonomy\TermStorage', $etm->getStorage('taxonomy_term'));
assertType('Drupal\search_api\Entity\SearchApiConfigEntityStorage', $etm->getStorage('search_api_index'));
assertType('Drupal\Core\Config\Entity\ConfigEntityStorage', $etm->getStorage('block'));
assertType('Drupal\Core\Entity\Sql\SqlContentEntityStorage', $etm->getStorage('content_entity_using_default_storage'));
assertType('Drupal\phpstan_fixtures\CustomContentEntityStorage', $etm->getStorage('content_entity_using_custom_storage'));
assertType('Drupal\Core\Config\Entity\ConfigEntityStorage', $etm->getStorage('config_entity_using_default_storage'));
assertType('Drupal\phpstan_fixtures\CustomConfigEntityStorage', $etm->getStorage('config_entity_using_custom_storage'));
const ENTITY_TYPE_ID_NODE = 'node';
assertType('Drupal\node\NodeStorage', $etm->getStorage(ENTITY_TYPE_ID_NODE));

// A first-class callable must not crash the analysis.
assertType('Closure(string): Drupal\Core\Entity\EntityStorageInterface', $etm->getStorage(...));

// A union of constant strings resolves each storage, not just the first.
$unionEntityTypeId = rand(0, 1) === 1 ? 'node' : 'user';
assertType('Drupal\node\NodeStorage|Drupal\user\UserStorage', $etm->getStorage($unionEntityTypeId));
assertType('Drupal\Core\Config\Entity\ConfigEntityStorage|Drupal\node\NodeStorage', $etm->getStorage(rand(0, 1) === 1 ? 'node' : 'block'));

// A dynamic entity type ID falls back to the declared return type.
$dynamicEntityTypeId = (string) rand(0, 1);
assertType('Drupal\Core\Entity\EntityStorageInterface', $etm->getStorage($dynamicEntityTypeId));
$displayContext = $dynamicEntityTypeId;
assertType('Drupal\Core\Entity\EntityStorageInterface', $etm->getStorage('entity_' . $displayContext . '_display'));

// Chained calls resolve every member of the union, in either operand order.
$nodeOrUser = $etm->getStorage(rand(0, 1) === 1 ? 'node' : 'user');
assertType('Drupal\node\Entity\Node|Drupal\user\Entity\User|null', $nodeOrUser->load(1));
assertType('array<int, Drupal\node\Entity\Node|Drupal\user\Entity\User>', $nodeOrUser->loadMultiple());
assertType('Drupal\node\Entity\Node|Drupal\user\Entity\User', $nodeOrUser->create([]));
$userOrNode = $etm->getStorage(rand(0, 1) === 1 ? 'user' : 'node');
assertType('Drupal\node\Entity\Node|Drupal\user\Entity\User|null', $userOrNode->load(1));

// Storages sharing a class stay distinct instead of collapsing to the base class.
$nodeOrDefault = $etm->getStorage(rand(0, 1) === 1 ? 'node' : 'content_entity_using_default_storage');
assertType('Drupal\Core\Entity\Sql\SqlContentEntityStorage|Drupal\node\NodeStorage', $nodeOrDefault);
assertType('Drupal\node\Entity\Node|Drupal\phpstan_fixtures\Entity\ContentEntityUsingDefaultStorage|null', $nodeOrDefault->load(1));
// PHPStan numbers members whose names collide; they are different storages.
$blockOrDefault = $etm->getStorage(rand(0, 1) === 1 ? 'block' : 'config_entity_using_default_storage');
assertType('Drupal\Core\Config\Entity\ConfigEntityStorage#1|Drupal\Core\Config\Entity\ConfigEntityStorage#2', $blockOrDefault);
assertType('array<string, Drupal\block\Entity\Block|Drupal\phpstan_fixtures\Entity\ConfigEntityUsingDefaultStorage>', $blockOrDefault->loadMultiple());

// A content and a config storage keep their own key types.
$nodeOrBlock = $etm->getStorage(rand(0, 1) === 1 ? 'node' : 'block');
assertType('array<int|string, Drupal\block\Entity\Block|Drupal\node\Entity\Node>', $nodeOrBlock->loadMultiple());

// A known and an unknown entity type ID fall back to the declared return type.
assertType('Drupal\Core\Entity\EntityStorageInterface', $etm->getStorage(rand(0, 1) === 1 ? 'node' : 'not_an_entity_type'));

// Narrowing a union member still works with the entity-type-aware comparison.
if ($nodeOrUser instanceof \Drupal\node\NodeStorage) {
    assertType('Drupal\node\NodeStorage', $nodeOrUser);
    assertType('Drupal\node\Entity\Node|null', $nodeOrUser->load(1));
} else {
    assertType('Drupal\user\UserStorage', $nodeOrUser);
}
