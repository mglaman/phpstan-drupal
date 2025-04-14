<?php

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\StringTranslation\TranslatableMarkup;

use function PHPStan\Testing\assertType;

function mixed_function(): mixed {
  return NULL;
}

// Inspector::assertAll()
$callable = fn (string $value): bool => $value === 'foo';
$input = mixed_function();
if (Inspector::assertAll($callable, $input)) {
    assertType("iterable", $input);
}
else {
    assertType('mixed~iterable', $input);
}

$input = mixed_function();
$callable = is_string(...);
if (Inspector::assertAll($callable, $input)) {
    assertType('iterable', $input);
}
else {
    assertType('mixed~iterable', $input);
}


// Inspector::assertAllStrings()
$input = mixed_function();
if (Inspector::assertAllStrings($input)) {
    assertType('iterable<string>', $input);
}
else {
    assertType('mixed~iterable<string>', $input);
}

// Inspector::assertAllStringable()
$input = mixed_function();
if (Inspector::assertAllStringable($input)) {
    assertType('iterable<string|Stringable>', $input);
}
else {
    assertType('mixed~iterable<string|Stringable>', $input);
}

// Inspector::assertAllArrays()
$input = mixed_function();
if (Inspector::assertAllArrays($input)) {
    assertType('iterable<array>', $input);
}
else {
    assertType('mixed~iterable<array>', $input);
}

// Inspector::assertStrictArray()
$input = mixed_function();
if (Inspector::assertStrictArray($input)) {
    assertType('array<int<0, max>, mixed>', $input);
}
else {
    assertType('mixed~array<int<0, max>, mixed>', $input);
}

// Inspector::assertAllStrictArrays()
$input = mixed_function();
if (Inspector::assertAllStrictArrays($input)) {
    assertType('iterable<array<int<0, max>, mixed>>', $input);
}
else {
    assertType('mixed~iterable<array<int<0, max>, mixed>>', $input);
}

// Inspector::assertAllHaveKey()
$input = mixed_function();
if (Inspector::assertAllHaveKey($input, 'foo', 'baz')) {
    assertType("iterable<non-empty-array&hasOffset('baz')&hasOffset('foo')>", $input);
}
else {
    assertType("mixed~iterable<non-empty-array&hasOffset('baz')&hasOffset('foo')>", $input);
}

// Inspector::assertAllIntegers()
$input = mixed_function();
if (Inspector::assertAllIntegers($input)) {
    assertType('iterable<int>', $input);
}
else {
    assertType('mixed~iterable<int>', $input);
}

// Inspector::assertAllFloat()
$input = mixed_function();
if (Inspector::assertAllFloat($input)) {
    assertType('iterable<float>', $input);
}
else {
    assertType('mixed~iterable<float>', $input);
}

// Inspector::assertAllCallable()
$input = mixed_function();
if (Inspector::assertAllCallable($input)) {
    assertType('iterable<callable(): mixed>', $input);
}
else {
    assertType('mixed~iterable<callable(): mixed>', $input);
}

// Inspector::assertAllNotEmpty()
$input = mixed_function();
if (Inspector::assertAllNotEmpty($input)) {
    assertType('iterable<float|int<min, -1>|int<1, max>|object|resource|non-empty-string|non-empty-array>', $input);
}
else {
    assertType('mixed~iterable<float|int<min, -1>|int<1, max>|object|resource|non-empty-string|non-empty-array>', $input);
}

// Inspector::assertAllNumeric()
$input = mixed_function();
if (Inspector::assertAllNumeric($input)) {
    assertType('iterable<float|int>', $input);
}
else {
    assertType('mixed~iterable<float|int>', $input);
}

// Inspector::assertAllMatch()
$pattern = 'foo';
$input = mixed_function();
if (Inspector::assertAllMatch($pattern, $input, false)) {
    assertType('iterable<string>', $input);
}
else {
    assertType('mixed~iterable<string>', $input);
}

// Inspector::assertAllRegularExpressionMatch()
$input = mixed_function();
if (Inspector::assertAllRegularExpressionMatch($pattern, $input)) {
    assertType('iterable<string>', $input);
}
else {
    assertType('mixed~iterable<string>', $input);
}

// Inspector::assertAllObjects()
$input = mixed_function();
if (Inspector::assertAllObjects($input, TranslatableMarkup::class, '\\Stringable', '\\Drupal\\jsonapi\\JsonApiResource\\ResourceIdentifier')) {
    assertType('iterable<\Drupal\jsonapi\JsonApiResource\ResourceIdentifier|\Stringable>', $input);
}
else {
    assertType('mixed~iterable<\Drupal\jsonapi\JsonApiResource\ResourceIdentifier|\Stringable>', $input);
}
