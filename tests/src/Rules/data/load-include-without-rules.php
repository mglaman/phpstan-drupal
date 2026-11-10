<?php

namespace LoadIncludeWithoutRules;

use Drupal\Core\Extension\ModuleHandlerInterface;

function load_and_call(ModuleHandlerInterface $moduleHandler): string {
    $moduleHandler->loadInclude('phpstan_fixtures', 'inc', 'phpstan_fixtures.loader');
    return phpstan_fixtures_function_from_load_include();
}
