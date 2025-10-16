<?php

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\StringTranslation\TranslatableMarkup;

use Drupal\node\Entity\Node;
use function PHPStan\Testing\assertType;

/**
 * @see Inspector::assertAll()
 */
function assertAll(mixed $mixed_arg, array $array_arg): void {
    $callable = is_string(...);

    Inspector::assertAll($callable, $mixed_arg)
        ? assertType('iterable', $mixed_arg)
        : assertType('mixed', $mixed_arg);

    Inspector::assertAll($callable, $array_arg)
        ? assertType('array', $array_arg)
        : assertType('array', $array_arg);
}

/**
 * @see Inspector::assertAllStrings()
 */
function assertAllStrings(mixed $mixed_arg, array $array_arg): void {
    Inspector::assertAllStrings($mixed_arg)
        ? assertType('iterable<string>', $mixed_arg)
        : assertType('mixed~iterable<string>', $mixed_arg);

    Inspector::assertAllStrings($array_arg)
        ? assertType('array<string>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertAllStringable()
 */
function assertAllStringable(mixed $mixed_arg, array $array_arg): void {
    Inspector::assertAllStringable($mixed_arg)
        ? assertType('iterable<string|Stringable>', $mixed_arg)
        : assertType('mixed~iterable<string|Stringable>', $mixed_arg);

    Inspector::assertAllStringable($array_arg)
        ? assertType('array<string|Stringable>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertAllArrays()
 */
function assertAllArrays(mixed $mixed_arg, array $array_arg): void {
    Inspector::assertAllArrays($mixed_arg)
        ? assertType('iterable<array>', $mixed_arg)
        : assertType('mixed~iterable<array>', $mixed_arg);

    Inspector::assertAllArrays($array_arg)
        ? assertType('array<array>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertStrictArray()
 */
function assertStrictArray(mixed $mixed_arg, array $array_arg): void {
    Inspector::assertStrictArray($mixed_arg)
        ? assertType('array<int<0, max>, mixed>', $mixed_arg)
        : assertType('mixed~array<int<0, max>, mixed>', $mixed_arg);

    Inspector::assertStrictArray($array_arg)
        ? assertType('array<int<0, max>, mixed>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertAllStrictArrays()
 */
function assertAllStrictArrays(mixed $mixed_arg, array $array_arg): void {
    Inspector::assertAllStrictArrays($mixed_arg)
        ? assertType('iterable<array<int<0, max>, mixed>>', $mixed_arg)
        : assertType('mixed~iterable<array<int<0, max>, mixed>>', $mixed_arg);

    Inspector::assertAllStrictArrays($array_arg)
        ? assertType('array<array<int<0, max>, mixed>>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertAllHaveKey()
 */
function assertAllHaveKey(mixed $mixed_arg, array $array_arg): void {
    Inspector::assertAllHaveKey($mixed_arg, 'foo', 'baz')
        ? assertType("iterable<non-empty-array&hasOffset('baz')&hasOffset('foo')>", $mixed_arg)
        : assertType("mixed~iterable<non-empty-array&hasOffset('baz')&hasOffset('foo')>", $mixed_arg);

    Inspector::assertAllHaveKey($array_arg, 'foo', 'baz')
        ? assertType("array<non-empty-array&hasOffset('baz')&hasOffset('foo')>", $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertAllIntegers()
 */
function assertAllIntegers(mixed $mixed_arg, array $array_arg): void {
    Inspector::assertAllIntegers($mixed_arg)
        ? assertType('iterable<int>', $mixed_arg)
        : assertType('mixed~iterable<int>', $mixed_arg);

    Inspector::assertAllIntegers($array_arg)
        ? assertType('array<int>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertAllFloat()
 */
function assertAllFloat(mixed $mixed_arg, array $array_arg): void {
    Inspector::assertAllFloat($mixed_arg)
        ? assertType('iterable<float>', $mixed_arg)
        : assertType('mixed~iterable<float>', $mixed_arg);

    Inspector::assertAllFloat($array_arg)
        ? assertType('array<float>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertAllCallable()
 */
function assertAllCallable(mixed $mixed_arg, array $array_arg): void {
    Inspector::assertAllCallable($mixed_arg)
        ? assertType('iterable<callable(): mixed>', $mixed_arg)
        : assertType('mixed~iterable<callable(): mixed>', $mixed_arg);

    Inspector::assertAllCallable($array_arg)
        ? assertType('array<callable(): mixed>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertAllNotEmpty()
 */
function assertAllNotEmpty(mixed $mixed_arg, array $array_arg): void {
    Inspector::assertAllNotEmpty($mixed_arg)
        ? assertType('iterable<float|int<min, -1>|int<1, max>|object|resource|non-empty-string|non-empty-array>', $mixed_arg)
        : assertType('mixed~iterable<float|int<min, -1>|int<1, max>|object|resource|non-empty-string|non-empty-array>', $mixed_arg);

    Inspector::assertAllNotEmpty($array_arg)
        ? assertType('array<float|int<min, -1>|int<1, max>|object|resource|non-empty-string|non-empty-array>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertAllNumeric()
 */
function assertAllNumeric(mixed $mixed_arg, array $array_arg): void {
    Inspector::assertAllNumeric($mixed_arg)
        ? assertType('iterable<float|int>', $mixed_arg)
        : assertType('mixed~iterable<float|int>', $mixed_arg);

    Inspector::assertAllNumeric($array_arg)
        ? assertType('array<float|int>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertAllMatch()
 */
function assertAllMatch(mixed $mixed_arg, array $array_arg): void {
    $pattern = 'foo';
    Inspector::assertAllMatch($pattern, $mixed_arg, false)
        ? assertType('iterable<string>', $mixed_arg)
        : assertType('mixed~iterable<string>', $mixed_arg);

    Inspector::assertAllMatch($pattern, $array_arg, false)
        ? assertType('array<string>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertAllRegularExpressionMatch()
 */
function assertAllRegularExpressionMatch(mixed $mixed_arg, array $array_arg): void {
    $pattern = 'foo';
    Inspector::assertAllRegularExpressionMatch($pattern, $mixed_arg)
        ? assertType('iterable<string>', $mixed_arg)
        : assertType('mixed~iterable<string>', $mixed_arg);

    Inspector::assertAllRegularExpressionMatch($pattern, $array_arg)
        ? assertType('array<string>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}

/**
 * @see Inspector::assertAllObjects()
 */
function assertAllObjects(mixed $mixed_arg, array $array_arg): void {
    // Note: 'TranslatableMarkup' is not mentioned in the final union type,
    // because it is a subtype of '\Stringable'.
    Inspector::assertAllObjects($mixed_arg, Node::class, TranslatableMarkup::class, '\\Stringable', '\\Drupal\\jsonapi\\JsonApiResource\\ResourceIdentifier')
        ? assertType('iterable<\Drupal\jsonapi\JsonApiResource\ResourceIdentifier|Drupal\node\Entity\Node|\Stringable>', $mixed_arg)
        : assertType('mixed~iterable<\Drupal\jsonapi\JsonApiResource\ResourceIdentifier|Drupal\node\Entity\Node|\Stringable>', $mixed_arg);

    Inspector::assertAllObjects($array_arg, Node::class, TranslatableMarkup::class, '\\Stringable', '\\Drupal\\jsonapi\\JsonApiResource\\ResourceIdentifier')
        ? assertType('array<\Drupal\jsonapi\JsonApiResource\ResourceIdentifier|Drupal\node\Entity\Node|\Stringable>', $array_arg)
        : assertType('non-empty-array', $array_arg);
}
