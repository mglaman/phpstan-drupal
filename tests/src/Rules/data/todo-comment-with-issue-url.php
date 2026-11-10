<?php

declare(strict_types=1);

// @todo This references the current issue https://drupal.org/i/3456789 and should fail.
$a = 1;

// @todo This references a different issue https://drupal.org/i/1234567 and should pass.
$b = 2;

// @todo This references the long-form URL https://drupal.org/project/drupal/issues/3456789 and should fail.
$c = 3;

// @todo This references a different project issue https://drupal.org/project/token/issues/9999999 and should pass.
$d = 4;

// This is just a regular comment with no todo.
$e = 5;

// @todo No URL here, just a plain todo.
$f = 6;

/**
 * @todo Block comment referencing current issue https://drupal.org/i/3456789 should fail.
 */
$g = 7;
