<?php

namespace EntityTypeManagerGetDefinition;

use function PHPStan\Testing\assertType;

$etm = \Drupal::entityTypeManager();

// When $exception_on_invalid is true (default), it should always return EntityTypeInterface
assertType('Drupal\Core\Entity\EntityTypeInterface', $etm->getDefinition('node'));
assertType('Drupal\Core\Entity\EntityTypeInterface', $etm->getDefinition('node', TRUE));

// When $exception_on_invalid is false, it may return EntityTypeInterface or null
assertType('Drupal\Core\Entity\EntityTypeInterface|null', $etm->getDefinition('non_existent_entity_type', FALSE));

// Test with variable parameter
$exception_on_invalid = false;
assertType('Drupal\Core\Entity\EntityTypeInterface|null', $etm->getDefinition('some_entity_type', $exception_on_invalid));

$exception_on_invalid = true;
assertType('Drupal\Core\Entity\EntityTypeInterface', $etm->getDefinition('some_entity_type', $exception_on_invalid));