<?php

namespace EntityOriginalProperty;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\module_installer_config_test\Entity\TestConfigType;

function test(ContentEntityInterface $entity, EntityTest $test_entity, TestConfigType $config, EntityInterface $any): void {
    $original = $entity->original;
    if (isset($entity->original)) {
        unset($entity->original);
    }
    $entity->original = $original;
    $test_entity->original;
    $config->original;
    // Not reported: PHPStan only asks extensions about the properties of
    // interfaces in universalObjectCratesClasses.
    $any->original;
    // Not deprecated.
    $entity->getOriginal();
    $entity->field_myfield;
    // Code that supports older Drupal versions is not reported.
    DeprecationHelper::backwardsCompatibleCall(
        \Drupal::VERSION,
        '11.2.0',
        fn () => $entity->getOriginal(),
        fn () => $entity->original,
    );
}
