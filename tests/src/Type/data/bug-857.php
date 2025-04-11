<?php

use Drupal\Component\Assertion\Inspector;
use function PHPStan\Testing\assertType;

function mixed_function(): mixed {}

// If the type is not narrowed from mixed, it is narrowed to 'iterable'.
$array = mixed_function();
assertType('mixed', $array);
Inspector::assertAll(fn (array $i): bool => TRUE, $array);
assertType('iterable', $array);

// If the type is narrowed before '::assertAll()', it shouldn't change it.
$array = mixed_function();
assertType('mixed', $array);
Inspector::assertAllArrays($array);
assertType('iterable<array>', $array);
Inspector::assertAll(fn (array $i): bool => TRUE, $array);
assertType('iterable<array>', $array);
