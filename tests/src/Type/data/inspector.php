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

// Inspector::assertAllArrays()
function mixed_array(): mixed {
  return [[],  []];
}

$input = [];
assert(Inspector::assertAllArrays($input));
assertType('array{}', $input);

$input = mixed_array();
$result = Inspector::assertAllArrays($input);
\assert(Inspector::assertAllArrays($input));
assertType('iterable<array<mixed, mixed>>', $input);
