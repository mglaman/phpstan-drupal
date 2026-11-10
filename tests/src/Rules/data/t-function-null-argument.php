<?php

declare(strict_types=1);

namespace TFunctionNullArgTest;

function testGlobalT(?string $name, int $count): void {
    t('@name is cool', ['@name' => null]);
    t('@name is cool', ['@name' => $name]);
    t('@count items', ['@count' => $count]);
}
