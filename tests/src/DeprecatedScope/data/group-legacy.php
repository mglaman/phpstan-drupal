<?php

namespace GroupLegacy;

use function Deprecated\deprecated_function;

/**
 * @group legacy
 */
final class FooTest {

    public function foo(): void {
        deprecated_function();
    }

}

final class BarTest {

    public function bar(): void {
        deprecated_function();
    }

    /**
     * @group legacy
     */
    public function barNot(): void {
        deprecated_function();
    }

}
