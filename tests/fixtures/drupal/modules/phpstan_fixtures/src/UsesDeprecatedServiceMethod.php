<?php

namespace Drupal\phpstan_fixtures;

use Drupal\Core\Entity\EntityManager;

class UsesDeprecatedServiceMethod {
    public function test() {
        $entity_manager = \Drupal::getContainer()->get('entity.manager');
        // Deprecated for EntityTypeManagerInterface::getDefinitions().
        $definitions = $entity_manager->getDefinitions();
    }
}
