<?php

namespace Foo;

use function PHPStan\Testing\assertType;

$lock = \Drupal::lock();

if (!$lock->acquire('foo')) {
    assertType('false', $lock->acquire('foo'));
    if ($lock->acquire('foo')) {
        assertType('true', $lock->acquire('foo'));
    }
}
