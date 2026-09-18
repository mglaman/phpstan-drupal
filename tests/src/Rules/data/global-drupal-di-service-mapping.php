<?php

namespace Drupal\phpstan_fixtures;

class TestServicesMappingExtension {
    public function testEntityManager() {
        $entity_manager = \Drupal::getContainer()->get('entity.manager');
        $doesNotExist = $entity_manager->thisMethodDoesNotExist();
        $definitions = $entity_manager->getDefinitions();
    }

    public function testPathAliasManagerServiceRename() {
        $manager = \Drupal::service('path.alias_manager');
        $path = $manager->getPathByAlias('/foo/bar', 'en');
    }
}
