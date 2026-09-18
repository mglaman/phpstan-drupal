<?php

function phpstan_fixtures_module_load_includes_negative_test(): void {
    $module_handler = \Drupal::moduleHandler();
    $module_handler->loadInclude('phpstan_fixtures', 'fetch.inc');
}
