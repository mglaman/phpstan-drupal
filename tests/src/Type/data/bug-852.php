<?php


use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Url;
use function PHPStan\Testing\assertType;

function foobar(array $baz): void {
    assert(Inspector::assertAllArrays($baz));
    assertType('array<int|string, array<int|string, mixed>>', $baz);

    assert(Inspector::assertAll(fn (array $i): bool => $i['file'] instanceof Url, $baz));
    assertType('array<int|string, array<int|string, array{file: \Drupal\Core\Url}>', $baz);

    assert(Inspector::assertAllHaveKey($baz, 'alt'));
    assertType('array<int|string, array<int|string, array{file: \Drupal\Core\Url, alt: mixed}>', $baz);
}
