<?php

namespace GroupLegacy;

use Drupal\Component\Utility\DeprecationHelper;
use function Deprecated\deprecated_function_call;

final class FooTest {

    public function methodCallingThings(): void {

        \Drupal\Component\Utility\DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '10.1.0', fn() => deprecated_function_call(), fn() => count([]));

        DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '10.1.0', fn() => deprecated_function_call(), fn() => count([]));

        deprecated_function_call();

    }

}
