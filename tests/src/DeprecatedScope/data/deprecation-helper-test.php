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

        DeprecationHelper::backwardsCompatibleCall(
            \Drupal::VERSION,
            '10.1.0',
            $this->currentCallable(...),
            $this->deprecatedCallable(...)
        );

        DeprecationHelper::backwardsCompatibleCall(
            \Drupal::VERSION,
            '10.1.0',
            // @todo somehow this should trigger an error as well.
            $this->deprecatedCallable(...),
            $this->currentCallable(...)
        );
    }

    /**
     * @deprecated
     *
     * @note if using reference callables they must be tagged as deprecated.
     */
    public function deprecatedCallable()
    {
        deprecated_function();
    }

    public function currentCallable()
    {
        count([]);
    }
}
