<?php


use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Url;
use function PHPStan\Testing\assertType;

function foo(array $baz): void {
    assert(Inspector::assertAllArrays($baz));
    assertType('array<mixed, array<mixed, mixed>>', $baz);

    assert(Inspector::assertAll(fn (array $i): bool => $i['file'] instanceof Url, $baz));
    assertType('array<mixed, array<mixed, mixed>>', $baz);

    assert(Inspector::assertAllHaveKey($baz, 'alt'));
    assertType("array<mixed, non-empty-array&hasOffset('alt')>", $baz);
}

function bar(array $zed): void {
    assert(Inspector::assertAll(fn (string $value) => $value === 'foo', $zed));
    assertType('array<mixed, mixed>', $zed);

}
