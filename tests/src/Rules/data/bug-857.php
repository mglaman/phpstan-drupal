<?php

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Url;

function foo(array $images) {
    // This call is crucial for the bug, because it narrows type to
    // 'array<array>'.
    Inspector::assertAllArrays($images);
    Inspector::assertAll(fn (array $i): bool => $i['file'] instanceof Url, $images);
    \PHPStan\Testing\assertType('', $images);
}
