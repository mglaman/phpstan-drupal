<?php

declare(strict_types=1);

namespace FormattableMarkupNullArgTest;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

// Error: null literal.
new FormattableMarkup('@name is cool', ['@name' => null]);

// Error: null literal in TranslatableMarkup.
new TranslatableMarkup('@name is cool', ['@name' => null]);

// Error: null literal in PluralTranslatableMarkup (arguments at index 3).
new PluralTranslatableMarkup(5, '1 item by @name', '@count items by @name', ['@name' => null]);

function testNullableVariable(?string $name): void {
    new FormattableMarkup('@name is cool', ['@name' => $name]);
    new TranslatableMarkup('@name is cool', ['@name' => $name]);
    new PluralTranslatableMarkup(5, '1 item by @name', '@count items by @name', ['@name' => $name]);
}

function testSafe(string $name): void {
    new FormattableMarkup('@name is cool', ['@name' => $name]);
    new TranslatableMarkup('@name is cool', ['@name' => $name]);
    new PluralTranslatableMarkup(5, '1 item by @name', '@count items by @name', ['@name' => $name]);
}

function testNoArguments(): void {
    new TranslatableMarkup('Hello world');
    new PluralTranslatableMarkup(5, '1 item', '@count items');
}

function testNullCoalescing(?string $name): void {
    new FormattableMarkup('@name is cool', ['@name' => $name ?? '']);
    new TranslatableMarkup('@name is cool', ['@name' => $name ?? '']);
    new PluralTranslatableMarkup(5, '1 item by @name', '@count items by @name', ['@name' => $name ?? '']);
}

function testMultipleArgs(?string $name, string $email): void {
    new FormattableMarkup('@name (@email)', ['@name' => $name, '@email' => $email]);
    new TranslatableMarkup('@name (@email)', ['@name' => $name, '@email' => $email]);
    new PluralTranslatableMarkup(5, '1 item by @name (@email)', '@count items by @name (@email)', ['@name' => $name, '@email' => $email]);
}

function testScalarAndStringable(int $count, float $ratio, bool $flag, FormattableMarkup $markup, \Stringable $stringable): void {
    new FormattableMarkup('@a @b @c @d @e', ['@a' => $count, '@b' => $ratio, '@c' => $flag, '@d' => $markup, '@e' => $stringable]);
    new TranslatableMarkup('@a @b @c @d @e', ['@a' => $count, '@b' => $ratio, '@c' => $flag, '@d' => $markup, '@e' => $stringable]);
    new PluralTranslatableMarkup(5, '1 item by @a', '@count items by @a', ['@a' => $count, '@b' => $markup]);
}

function testDynamicKey(string $key, string $value): void {
    new FormattableMarkup('test', [$key => $value]);
    new TranslatableMarkup('test', [$key => $value]);
    new PluralTranslatableMarkup(5, '1 test', '@count tests', [$key => $value]);
}

function testArrayValue(): void {
    new FormattableMarkup('@name', ['@name' => ['nested']]);
}
