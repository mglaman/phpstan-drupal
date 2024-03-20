<?php

namespace Foo;

use function PHPStan\Testing\assertType;

$lock = \Drupal::lock();

if (!$lock->acquire('foo')) {
    assertType('bool', $lock->acquire('foo'));
    if ($lock->acquire('foo')) {
        assertType('bool', $lock->acquire('foo'));
    }
}
