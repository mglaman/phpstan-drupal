<?php

use Drupal\Component\Assertion\Inspector;

use function PHPStan\Testing\assertType;

// Inspector::assertAllStrings()
function mixed_all_strings(): mixed {
  return ['foo', 'bar'];
}

$input = [];
assert(Inspector::assertAllStrings($input));
assertType('array{}', $input);

$input = mixed_all_strings();
assert(Inspector::assertAllStrings($input));
assertType('iterable<string>', $input);
assertType('string', iterator_to_array($input, FALSE)[0]);

// Inspector::assertAllStringable()
class StringableClass implements \Stringable {
    public function __toString(): string {
      return 'foo';
    }
}

function mixed_string_and_stringable(): mixed {
  return ['foo', new StringableClass()];
}

$input = [];
assert(Inspector::assertAllStringable($input));
assertType('array{}', $input);

$input = mixed_string_and_stringable();
assert(Inspector::assertAllStringable($input));
assertType('iterable<string|Stringable>', $input);
assertType('string|Stringable', iterator_to_array($input, FALSE)[0]);

// Inspector::assertAllArrays()
function mixed_array(): mixed {
  return [[], []];
}

$input = [];
assert(Inspector::assertAllArrays($input));
assertType('array{}', $input);

$input = mixed_array();
$result = Inspector::assertAllArrays($input);
\assert(Inspector::assertAllArrays($input));
assertType('iterable<array<mixed, mixed>>', $input);

// Inspector::assertStrictArray()
function mixed_strict_array(): mixed {
  return ['foo', 'bar'];
}

$input = [];
assert(Inspector::assertStrictArray($input));
assertType('array{}', $input);

$input = mixed_strict_array();
assert(Inspector::assertStrictArray($input));
assertType('array<int<0, max>, mixed>', $input);
assertType('mixed', $input[0]);

// Inspector::assertAllStrictArrays()
function mixed_all_strict_arrays(): mixed {
  return [['foo', 'bar'], ['foo', 'bar']];
}

$input = [];
assert(Inspector::assertStrictArray($input));
assertType('array{}', $input);

$input = mixed_all_strict_arrays();
assert(Inspector::assertAllStrictArrays($input));
assertType('iterable<array<int<0, max>, mixed>>', $input);
assertType('array<int<0, max>, mixed>', iterator_to_array($input, FALSE)[0]);

// Inspector::assertAllHaveKey()
function mixed_keyed_arrays(): mixed {
  return [['foo' => 'bar'], ['baz' => 'bar']];
}

$input = [];
assert(Inspector::assertAllHaveKey($input));
assertType('array{}', $input);

$input = mixed_keyed_arrays();
assert(Inspector::assertAllHaveKey($input, 'foo', 'baz'));
assertType('array{}', $input);