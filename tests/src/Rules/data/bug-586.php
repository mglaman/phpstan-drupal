<?php

namespace Bug586;

use Drupal\Core\Entity\ContentEntityStorageInterface;

$nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
$nodeStorage->loadRevision(1);

$genericContentEntityStorage = \Drupal::entityTypeManager()->getStorage('foo');
assert($genericContentEntityStorage instanceof ContentEntityStorageInterface);
$genericContentEntityStorage->loadRevision(1);
