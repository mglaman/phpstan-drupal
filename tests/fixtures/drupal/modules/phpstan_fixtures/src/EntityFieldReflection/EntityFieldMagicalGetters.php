<?php

namespace Drupal\phpstan_fixtures\EntityFieldReflection;

use Drupal\entity_test\Entity\EntityTest;

class EntityFieldMagicalGetters {
    public function testLabel() {

        /** @var EntityTest $testEntity */
        $testEntity = EntityTest::create([
            'name' => 'Llama',
            'type' => 'entity_test',
        ]);

        // 🤦‍♂
        $label1 = $testEntity->label();
        // @todo Access to an undefined property Drupal\Core\TypedData\TypedDataInterface::$value.
        $label2 = $testEntity->get('name')->first()->value;
        // @todo Access to an undefined property Drupal\Core\TypedData\TypedDataInterface::$value.
        $label3 = $testEntity->name->first()->value;
        // This doesn't fail because of EntityFieldsViaMagicReflectionExtension
        $label4 = $testEntity->name->value;
    }
}
