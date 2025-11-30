<?php

namespace IgnoreDeprecations;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use function Deprecated\deprecated_function;

#[IgnoreDeprecations]
final class FooTest {

    public function foo(): void {
        deprecated_function();
    }

}

final class BarTest {

    public function bar(): void {
        deprecated_function();
    }

    #[IgnoreDeprecations]
    public function barNot(): void {
        deprecated_function();
    }

}

#[IgnoreDeprecations('baz')]
final class FooBazTest {

    public function foo(): void {
        deprecated_function();
    }

}

final class BarBazTest {

    public function bar(): void {
        deprecated_function();
    }

    #[IgnoreDeprecations('baz')]
    public function barNot(): void {
        deprecated_function();
    }

}
