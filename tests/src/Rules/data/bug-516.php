<?php

namespace Bug516;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Tests moduleHandler::loadInclude where first argument is a variable.
 */
class TestClass {

    /**
     * Code snippet from \Drupal\TestTools\Extension\SchemaInspector.
     *
     * @param \Drupal\Core\Extension\ModuleHandlerInterface $handler
     * @param string $module
     */
    function bug516(ModuleHandlerInterface $handler, string $module): void
    {
        $handler->loadInclude($module, 'install');
    }

    /**
     * Code snippet from \_update_fix_missing_schema.
     */
    function bug516_2(): void
    {
        $module_handler = \Drupal::moduleHandler();
        $enabled_modules = $module_handler->getModuleList();

        foreach (array_keys($enabled_modules) as $module) {
            $module_handler->loadInclude($module, 'install');
        }
    }

}
