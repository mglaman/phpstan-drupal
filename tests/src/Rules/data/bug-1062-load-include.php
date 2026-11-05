<?php

namespace Bug1062LoadInclude;

function test(): void {
    \Drupal::moduleHandler()->loadInclude('contained_not_initialized', 'inc', 'contained_not_initialized.load_include');
}
