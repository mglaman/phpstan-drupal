<?php

namespace Drupal\phpstan_fixtures;

use Drupal\Core\Entity\EntityManager;

class TestServicesMappingExtension {
    public function test() {
        $entity_manager = \Drupal::getContainer()->get('entity.manager');
        $doesNotExist = $entity_manager->thisMethodDoesNotExist();
        // @todo this should be throwing deprecations...
        $definitions = $entity_manager->getDefinitions();
    }
}
