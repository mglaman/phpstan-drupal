<?php

use Drupal\Component\Assertion\Inspector;
use function PHPStan\Testing\assertType;

function mixed_function(): mixed {}

// If the type is not narrowed from 'mixed' before call, it is narrowed to
// 'iterable'.
$array = mixed_function();
assertType('mixed', $array);
\assert(Inspector::assertAll(fn (array $i): bool => TRUE, $array));
assertType('iterable', $array);

// If the type is narrowed before '::assertAll()', it shouldn't change it.
$array = mixed_function();
assertType('mixed', $array);
\assert(Inspector::assertAllArrays($array));
assertType('iterable<array>', $array);
\assert(Inspector::assertAll(fn (array $i): bool => TRUE, $array));
assertType('iterable<array>', $array);
