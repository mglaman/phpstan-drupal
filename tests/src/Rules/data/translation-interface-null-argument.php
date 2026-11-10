<?php

declare(strict_types=1);

namespace TranslationInterfaceNullArgTest;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

function testNull(TranslationInterface $translation): void {
    $translation->translate('@name is cool', ['@name' => null]);
    $translation->formatPlural(5, '1 item by @name', '@count items by @name', ['@name' => null]);
}

function testNullable(TranslationInterface $translation, ?string $name): void {
    $translation->translate('@name is cool', ['@name' => $name]);
    $translation->formatPlural(5, '1 item by @name', '@count items by @name', ['@name' => $name]);
}

function testSafe(TranslationInterface $translation, string $name): void {
    $translation->translate('@name is cool', ['@name' => $name]);
    $translation->formatPlural(5, '1 item by @name', '@count items by @name', ['@name' => $name]);
}

function testNullCoalescing(TranslationInterface $translation, ?string $name): void {
    $translation->translate('@name is cool', ['@name' => $name ?? '']);
    $translation->formatPlural(5, '1 item by @name', '@count items by @name', ['@name' => $name ?? '']);
}

function testNoArguments(TranslationInterface $translation): void {
    $translation->translate('Hello world');
    $translation->formatPlural(5, '1 item', '@count items');
}

function testScalar(TranslationInterface $translation, int $count): void {
    $translation->translate('@count', ['@count' => $count]);
    $translation->formatPlural($count, '1 item', '@count items', ['@total' => $count]);
}

function testDynamicKey(TranslationInterface $translation, string $key, string $value): void {
    $translation->translate('test', [$key => $value]);
    $translation->formatPlural(5, '1 test', '@count tests', [$key => $value]);
}

final class UsesStringTranslationTrait {
    use StringTranslationTrait;

    public function testNull(?string $name, int $count): void {
        $this->t('@name is cool', ['@name' => null]);
        $this->formatPlural(5, '1 item by @name', '@count items by @name', ['@name' => $name]);
        $this->t('@count', ['@count' => $count]);
    }
}
