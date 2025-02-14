<?php

use Drupal\Component\Assertion\Inspector;

use function PHPStan\Testing\assertType;

function mixed_function(): mixed {
  return NULL;
}

// Inspector::assertAllStrings()
$input = mixed_function();
assert(Inspector::assertAllStrings($input));
assertType('iterable<string>', $input);

// Inspector::assertAllStringable()
$input = mixed_function();
assert(Inspector::assertAllStringable($input));
assertType('iterable<string|Stringable>', $input);

// Inspector::assertAllArrays()
$input = mixed_function();
\assert(Inspector::assertAllArrays($input));
assertType('iterable<array<mixed, mixed>>', $input);

// Inspector::assertStrictArray()
$input = mixed_function();
assert(Inspector::assertStrictArray($input));
assertType('array<int<0, max>, mixed>', $input);

// Inspector::assertAllStrictArrays()
$input = mixed_function();
assert(Inspector::assertAllStrictArrays($input));
assertType('iterable<array<int<0, max>, mixed>>', $input);

// Inspector::assertAllHaveKey()
$input = mixed_function();
assert(Inspector::assertAllHaveKey($input, 'foo', 'baz'));
assertType("array<mixed, array<hasOffset('baz')&hasOffset('foo'), mixed>>", $input);

// Inspector::assertAllIntegers()
$input = mixed_function();
assert(Inspector::assertAllIntegers($input));
assertType('iterable<int>', $input);

// Inspector::assertAllFloat()
$input = mixed_function();
assert(Inspector::assertAllFloat($input));
assertType('iterable<float>', $input);

// Inspector::assertAllCallable()
$input = mixed_function();
assert(Inspector::assertAllCallable($input));
assertType('iterable<callable(): mixed>', $input);

// Inspector::assertAllNotEmpty()
$input = mixed_function();
assert(Inspector::assertAllNotEmpty($input));
assertType('iterable<float|int<min, -1>|int<1, max>|object|resource|non-empty-string|non-empty-array>', $input);
