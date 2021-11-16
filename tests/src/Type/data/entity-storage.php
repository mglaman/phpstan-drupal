<?php

namespace DrupalEntityStorage;

use function PHPStan\Testing\assertType;

// Content Entity types.
$nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
assertType('Drupal\node\Entity\Node', $nodeStorage->create(['type' => 'page', 'title' => 'foo']));
assertType('Drupal\node\Entity\Node|null', $nodeStorage->load(42));
assertType('Drupal\node\Entity\Node|null', $nodeStorage->load('42'));
assertType('Drupal\node\Entity\Node|null', $nodeStorage->loadUnchanged(42));
assertType('Drupal\node\Entity\Node|null', $nodeStorage->loadUnchanged('42'));
assertType('array<int, Drupal\node\Entity\Node>', $nodeStorage->loadMultiple([42, 29]));
assertType('array<int, Drupal\node\Entity\Node>', $nodeStorage->loadMultiple(['42', '29']));
assertType('array<int, Drupal\node\Entity\Node>', $nodeStorage->loadMultiple(NULL));
assertType('array<int, Drupal\node\Entity\Node>', $nodeStorage->loadByProperties([]));
assertType('Drupal\node\Entity\Node', \Drupal::entityTypeManager()->getStorage('node')->create(['type' => 'page', 'title' => 'foo']));
assertType('Drupal\node\Entity\Node|null', \Drupal::entityTypeManager()->getStorage('node')->load(42));
assertType('Drupal\node\Entity\Node|null', \Drupal::entityTypeManager()->getStorage('node')->load('42'));
assertType('Drupal\node\Entity\Node|null', \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged(42));
assertType('Drupal\node\Entity\Node|null', \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged('42'));
assertType('array<int, Drupal\node\Entity\Node>', \Drupal::entityTypeManager()->getStorage('node')->loadMultiple([42, 29]));
assertType('array<int, Drupal\node\Entity\Node>', \Drupal::entityTypeManager()->getStorage('node')->loadMultiple(['42', '29']));
assertType('array<int, Drupal\node\Entity\Node>', \Drupal::entityTypeManager()->getStorage('node')->loadMultiple(NULL));
assertType('array<int, Drupal\node\Entity\Node>', \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([]));

$termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
assertType('Drupal\taxonomy\Entity\Term', $termStorage->create(['vid' => 'tag', 'name' => 'foo']));
assertType('Drupal\taxonomy\Entity\Term|null', $termStorage->load(42));
assertType('Drupal\taxonomy\Entity\Term|null', $termStorage->load('42'));
assertType('Drupal\taxonomy\Entity\Term|null', $termStorage->loadUnchanged(42));
assertType('Drupal\taxonomy\Entity\Term|null', $termStorage->loadUnchanged('42'));
assertType('array<int, Drupal\taxonomy\Entity\Term>', $termStorage->loadMultiple([42, 29]));
assertType('array<int, Drupal\taxonomy\Entity\Term>', $termStorage->loadMultiple(['42', '29']));
assertType('array<int, Drupal\taxonomy\Entity\Term>', $termStorage->loadMultiple(NULL));
assertType('array<int, Drupal\taxonomy\Entity\Term>', $termStorage->loadByProperties([]));

$userStorage = \Drupal::entityTypeManager()->getStorage('user');
assertType('Drupal\user\Entity\User', $userStorage->create(['name' => 'foo']));
assertType('Drupal\user\Entity\User|null', $userStorage->load(42));
assertType('Drupal\user\Entity\User|null', $userStorage->load('42'));
assertType('Drupal\user\Entity\User|null', $userStorage->loadUnchanged(42));
assertType('Drupal\user\Entity\User|null', $userStorage->loadUnchanged('42'));
assertType('array<int, Drupal\user\Entity\User>', $userStorage->loadMultiple([42, 29]));
assertType('array<int, Drupal\user\Entity\User>', $userStorage->loadMultiple(['42', '29']));
assertType('array<int, Drupal\user\Entity\User>', $userStorage->loadMultiple(NULL));
assertType('array<int, Drupal\user\Entity\User>', $userStorage->loadByProperties([]));

$defaultStorage = \Drupal::entityTypeManager()->getStorage('content_entity_using_default_storage');
assertType('Drupal\phpstan_fixtures\Entity\ContentEntityUsingDefaultStorage', $defaultStorage->create([ 'title' => 'foo']));
assertType('Drupal\phpstan_fixtures\Entity\ContentEntityUsingDefaultStorage|null', $defaultStorage->load(42));
assertType('Drupal\phpstan_fixtures\Entity\ContentEntityUsingDefaultStorage|null', $defaultStorage->load('42'));
assertType('Drupal\phpstan_fixtures\Entity\ContentEntityUsingDefaultStorage|null', $defaultStorage->loadUnchanged(42));
assertType('Drupal\phpstan_fixtures\Entity\ContentEntityUsingDefaultStorage|null', $defaultStorage->loadUnchanged('42'));
assertType('array<int, Drupal\phpstan_fixtures\Entity\ContentEntityUsingDefaultStorage>', $defaultStorage->loadMultiple([42, 29]));
assertType('array<int, Drupal\phpstan_fixtures\Entity\ContentEntityUsingDefaultStorage>', $defaultStorage->loadMultiple(['42', '29']));
assertType('array<int, Drupal\phpstan_fixtures\Entity\ContentEntityUsingDefaultStorage>', $defaultStorage->loadMultiple(NULL));
assertType('array<int, Drupal\phpstan_fixtures\Entity\ContentEntityUsingDefaultStorage>', $defaultStorage->loadByProperties([]));

$customStorage = \Drupal::entityTypeManager()->getStorage('content_entity_using_custom_storage');
assertType('Drupal\phpstan_fixtures\Entity\ContentEntityUsingCustomStorage', $customStorage->create([ 'title' => 'foo']));
assertType('Drupal\phpstan_fixtures\Entity\ContentEntityUsingCustomStorage|null', $customStorage->load(42));
assertType('Drupal\phpstan_fixtures\Entity\ContentEntityUsingCustomStorage|null', $customStorage->load('42'));
assertType('Drupal\phpstan_fixtures\Entity\ContentEntityUsingCustomStorage|null', $customStorage->loadUnchanged(42));
assertType('Drupal\phpstan_fixtures\Entity\ContentEntityUsingCustomStorage|null', $customStorage->loadUnchanged('42'));
assertType('array<int, Drupal\phpstan_fixtures\Entity\ContentEntityUsingCustomStorage>', $customStorage->loadMultiple([42, 29]));
assertType('array<int, Drupal\phpstan_fixtures\Entity\ContentEntityUsingCustomStorage>', $customStorage->loadMultiple(['42', '29']));
assertType('array<int, Drupal\phpstan_fixtures\Entity\ContentEntityUsingCustomStorage>', $customStorage->loadMultiple(NULL));
assertType('array<int, Drupal\phpstan_fixtures\Entity\ContentEntityUsingCustomStorage>', $customStorage->loadByProperties([]));

// Config Entity types.
$blockStorage = \Drupal::entityTypeManager()->getStorage('block');
assertType('Drupal\block\Entity\Block', $blockStorage->create(['plugin' => 'the_id', 'theme' => 'the_theme']));
assertType('Drupal\block\Entity\Block|null', $blockStorage->load(42));
assertType('Drupal\block\Entity\Block|null', $blockStorage->load('dummy_block'));
assertType('Drupal\block\Entity\Block|null', $blockStorage->loadUnchanged(42));
assertType('Drupal\block\Entity\Block|null', $blockStorage->loadUnchanged('dummy_block'));
assertType('array<string, Drupal\block\Entity\Block>', $blockStorage->loadMultiple(['dummy_block', 'other']));
assertType('array<string, Drupal\block\Entity\Block>', $blockStorage->loadMultiple(NULL));
assertType('array<string, Drupal\block\Entity\Block>', $blockStorage->loadByProperties([]));
assertType('Drupal\block\Entity\Block',\Drupal::entityTypeManager()->getStorage('block')->create(['plugin' => 'the_id', 'theme' => 'the_theme']));
assertType('Drupal\block\Entity\Block|null', \Drupal::entityTypeManager()->getStorage('block')->load(42));
assertType('Drupal\block\Entity\Block|null', \Drupal::entityTypeManager()->getStorage('block')->load('dummy_block'));
assertType('Drupal\block\Entity\Block|null', \Drupal::entityTypeManager()->getStorage('block')->loadUnchanged(42));
assertType('Drupal\block\Entity\Block|null', \Drupal::entityTypeManager()->getStorage('block')->loadUnchanged('dummy_block'));
assertType('array<string, Drupal\block\Entity\Block>', \Drupal::entityTypeManager()->getStorage('block')->loadMultiple(['dummy_block', 'other']));
assertType('array<string, Drupal\block\Entity\Block>', \Drupal::entityTypeManager()->getStorage('block')->loadMultiple(NULL));
assertType('array<string, Drupal\block\Entity\Block>', \Drupal::entityTypeManager()->getStorage('block')->loadByProperties([]));

$defaultStorage = \Drupal::entityTypeManager()->getStorage('config_entity_using_default_storage');
assertType('Drupal\phpstan_fixtures\Entity\ConfigEntityUsingDefaultStorage', $defaultStorage->create(['plugin_id' => 'foo']));
assertType('Drupal\phpstan_fixtures\Entity\ConfigEntityUsingDefaultStorage|null', $defaultStorage->load(42));
assertType('Drupal\phpstan_fixtures\Entity\ConfigEntityUsingDefaultStorage|null', $defaultStorage->load('foo'));
assertType('Drupal\phpstan_fixtures\Entity\ConfigEntityUsingDefaultStorage|null', $defaultStorage->loadUnchanged(42));
assertType('Drupal\phpstan_fixtures\Entity\ConfigEntityUsingDefaultStorage|null', $defaultStorage->loadUnchanged('foo'));
assertType('array<string, Drupal\phpstan_fixtures\Entity\ConfigEntityUsingDefaultStorage>', $defaultStorage->loadMultiple([42, 29]));
assertType('array<string, Drupal\phpstan_fixtures\Entity\ConfigEntityUsingDefaultStorage>', $defaultStorage->loadMultiple(['foo', 'bar']));
assertType('array<string, Drupal\phpstan_fixtures\Entity\ConfigEntityUsingDefaultStorage>', $defaultStorage->loadMultiple(NULL));
assertType('array<string, Drupal\phpstan_fixtures\Entity\ConfigEntityUsingDefaultStorage>', $defaultStorage->loadByProperties([]));

$customStorage = \Drupal::entityTypeManager()->getStorage('config_entity_using_custom_storage');
assertType('Drupal\phpstan_fixtures\Entity\ConfigEntityUsingCustomStorage', $customStorage->create(['plugin_id' => 'foo']));
assertType('Drupal\phpstan_fixtures\Entity\ConfigEntityUsingCustomStorage|null', $customStorage->load(42));
assertType('Drupal\phpstan_fixtures\Entity\ConfigEntityUsingCustomStorage|null', $customStorage->load('foo'));
assertType('Drupal\phpstan_fixtures\Entity\ConfigEntityUsingCustomStorage|null', $customStorage->loadUnchanged(42));
assertType('Drupal\phpstan_fixtures\Entity\ConfigEntityUsingCustomStorage|null', $customStorage->loadUnchanged('foo'));
assertType('array<string, Drupal\phpstan_fixtures\Entity\ConfigEntityUsingCustomStorage>', $customStorage->loadMultiple([42, 29]));
assertType('array<string, Drupal\phpstan_fixtures\Entity\ConfigEntityUsingCustomStorage>', $customStorage->loadMultiple(['foo', 'bar']));
assertType('array<string, Drupal\phpstan_fixtures\Entity\ConfigEntityUsingCustomStorage>', $customStorage->loadMultiple(NULL));
assertType('array<string, Drupal\phpstan_fixtures\Entity\ConfigEntityUsingCustomStorage>', $customStorage->loadByProperties([]));

// Entity type id unknown from phpstan-drupal config: keep default types from EntityStorageInterface.
$storage = \Drupal::entityTypeManager()->getStorage('unknown_entity_type_id');
assertType('Drupal\Core\Entity\EntityInterface', $storage->create(['name' => 'foo']));
assertType('Drupal\Core\Entity\EntityInterface|null', $storage->load(42));
assertType('Drupal\Core\Entity\EntityInterface|null', $storage->load('42'));
assertType('Drupal\Core\Entity\EntityInterface|null', $storage->loadUnchanged(42));
assertType('Drupal\Core\Entity\EntityInterface|null', $storage->loadUnchanged('42'));
assertType('array<Drupal\Core\Entity\EntityInterface>', $storage->loadMultiple([42, 29]));
assertType('array<Drupal\Core\Entity\EntityInterface>', $storage->loadMultiple(['42', '29']));
assertType('array<Drupal\Core\Entity\EntityInterface>', $storage->loadMultiple(NULL));
assertType('array<Drupal\Core\Entity\EntityInterface>', $storage->loadByProperties([]));

// Just reusing the same $storage to validate the type is correctly inferred.
$storage = \Drupal::entityTypeManager()->getStorage('node');
assertType('Drupal\node\Entity\Node', $storage->create(['type' => 'page', 'title' => 'foo']));
assertType('Drupal\node\Entity\Node|null', $storage->load(42));
assertType('Drupal\node\Entity\Node|null', $storage->load('42'));
assertType('Drupal\node\Entity\Node|null', $storage->loadUnchanged(42));
assertType('Drupal\node\Entity\Node|null', $storage->loadUnchanged('42'));
assertType('array<int, Drupal\node\Entity\Node>', $storage->loadMultiple([42, 29]));
assertType('array<int, Drupal\node\Entity\Node>', $storage->loadMultiple(['42', '29']));
assertType('array<int, Drupal\node\Entity\Node>', $storage->loadMultiple(NULL));
assertType('array<int, Drupal\node\Entity\Node>', $storage->loadByProperties([]));
