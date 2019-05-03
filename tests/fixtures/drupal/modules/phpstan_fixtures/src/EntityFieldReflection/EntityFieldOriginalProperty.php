<?php

namespace Drupal\phpstan_fixtures\EntityFieldReflection;

use Drupal\entity_test\Entity\EntityTest;

class EntityFieldOriginalProperty {

    public function testOriginal() {
        /** @var EntityTest $testEntity */
        $testEntity = EntityTest::create([
            'name' => 'Llama',
            'type' => 'entity_test',
        ]);
        if ($testEntity->getRevisionId() !== $testEntity->original->getRevisionId()) {
            $testEntity->setSyncing(TRUE);
        }

        if (empty($testEntity->original) || $testEntity->getRevisionId() !== $testEntity->original->getRevisionId()) {
            $testEntity->setSyncing(TRUE);
        }
    }
}
