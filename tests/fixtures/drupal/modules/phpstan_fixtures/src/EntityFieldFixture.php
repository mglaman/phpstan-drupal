<?php

namespace Drupal\phpstan_fixtures;

use Drupal\entity_test\Entity\EntityTest;

class EntityFieldFixture {
    public function testMagicalFanciness() {

        /** @var EntityTest $testEntity */
        $testEntity = EntityTest::create([
            'name' => 'Llama',
            'type' => 'entity_test',
        ]);

        // 🤦‍♂
        $label1 = $testEntity->label();
        $label2 = $testEntity->get('name')->first()->value;
        $label3 = $testEntity->name->first()->value;
        $label4 = $testEntity->name->value;

    }
}
