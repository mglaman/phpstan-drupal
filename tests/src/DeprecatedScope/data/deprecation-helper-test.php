<?php

namespace GroupLegacy;

use Drupal\Component\Utility\DeprecationHelper;
use function Deprecated\deprecated_function;

final class FooTest {

    public function methodCallingThings(): void {

        \Drupal\Component\Utility\DeprecationHelper::backwardsCompatibleCall(
            \Drupal::VERSION,
            '10.1.0',
            fn() => count([]),
            fn() => deprecated_function()
        );

        DeprecationHelper::backwardsCompatibleCall(
            \Drupal::VERSION,
            '10.1.0',
            fn() => count([]),
            fn() => deprecated_function()
        );

        deprecated_function();

        DeprecationHelper::backwardsCompatibleCall(
            \Drupal::VERSION,
            '10.1.0',
            function() {
                count([]);
            },
            function() {
                deprecated_function();
            }
        );

        DeprecationHelper::backwardsCompatibleCall(
            \Drupal::VERSION,
            '10.1.0',
            fn() => deprecated_function(),
            fn() => count([])
        );
    }
}
