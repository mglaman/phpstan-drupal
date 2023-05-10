<?php

namespace Bug177;

function foo() {
    \Drupal::moduleHandler()->loadInclude('locale', 'fetch.php');
    $moduleHandler = \Drupal::moduleHandler();
    $moduleHandler->loadInclude('locale', 'fetch.php');
}
