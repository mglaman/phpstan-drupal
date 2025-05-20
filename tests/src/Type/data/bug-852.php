<?php


use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Url;
use function PHPStan\Testing\assertType;

function foo(array $baz): void {
    assert(Inspector::assertAllArrays($baz));
    assertType('array<array>', $baz);

    assert(Inspector::assertAll(fn (array $i): bool => $i['file'] instanceof Url, $baz));
    assertType('array<array>', $baz);

    assert(Inspector::assertAllHaveKey($baz, 'alt'));
    assertType("array<non-empty-array&hasOffset('alt')>", $baz);
}

function bar(array $zed): void {
    assert(Inspector::assertAll(fn (string $value) => $value === 'foo', $zed));
    assertType('array', $zed);

}

/**
 * @param array $images
 *   Images of the project. Each item needs to be an array with two elements:
 *   `file`, which is a \Drupal\Core\Url object pointing to the image, and
 *   `alt`, which is the alt text.
 */
function project_browser_example(array $images) {
    assert(
        Inspector::assertAllArrays($images) &&
        Inspector::assertAllHaveKey($images, 'file') &&
        Inspector::assertAll(fn (array $i): bool => $i['file'] instanceof Url, $images) &&
        Inspector::assertAllHaveKey($images, 'alt')
    ) or throw new \InvalidArgumentException('The project images must be arrays with `file` and `alt` elements.');
    assertType("array<non-empty-array&hasOffset('alt')&hasOffset('file')>", $images);
}
