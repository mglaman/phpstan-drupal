<?php

namespace PhpstanDrupalAccessible;

use Drupal\Core\Access\AccessResultInterface;
use function PHPStan\Testing\assertType;

$entityTypeManager = \Drupal::entityTypeManager();
$entity = $entityTypeManager->getStorage('entity_test')->create();

assertType('bool', $entity->access('add'));
assertType('bool', $entity->access('add', null));
assertType('bool', $entity->access('add', null, false));
assertType(AccessResultInterface::class, $entity->access('add', null, true));
