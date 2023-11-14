<?php

namespace Bug547;

/**
 * Tests moduleHandler::loadInclude where $name argument includes a variable.
 */
class TestClass {

    /**
     * Code snippet from core/modules/system/tests/modules/entity_test/entity_test.install.
     */
    function bug547(): void
    {
        $module_handler = \Drupal::moduleHandler();
        $index = \Drupal::state()->get('entity_test.db_updates.entity_definition_updates');
        $module_handler->loadInclude('entity_test', 'inc', 'update/entity_definition_updates_' . $index);
    }

}

