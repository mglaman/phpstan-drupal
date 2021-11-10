<?php

namespace EntityTypeManagerGetStorage;

use function PHPStan\Testing\assertType;

$etm = \Drupal::entityTypeManager();

assertType('Drupal\node\NodeStorage', $etm->getStorage('node'));
assertType('Drupal\user\UserStorage', $etm->getStorage('user'));
assertType('Drupal\Core\Entity\EntityStorageInterface', $etm->getStorage('search_api_index'));
