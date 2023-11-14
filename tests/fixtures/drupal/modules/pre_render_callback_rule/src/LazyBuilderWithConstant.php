<?php declare(strict_types=1);

namespace Drupal\pre_render_callback_rule;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

class LazyBuilderWithConstant implements TrustedCallbackInterface {

    public function staticCallback(): array {
        return [
            'foo' => [
                '#lazy_builder' => [[self::class, 'lazyBuilder'], ['baz', true]],
            ],
            'bar' => [
                '#lazy_builder' => [[static::class, 'lazyBuilder'], ['mars', false]],
            ],
            'baz' => [
                '#lazy_builder' => [[$this, 'lazyBuilder'], ['mars', false]],
            ],
            'abc' => [
                '#lazy_builder' => [self::class . '::lazyBuilder', ['baz', true]],
            ],
            'def' => [
                '#lazy_builder' => [static::class . '::lazyBuilder', ['mars', false]],
            ],
        ];
    }

    public static function lazyBuilder(string $foo, bool $bar) {
        return [
            '#markup' => "$foo $bar",
        ];
    }

    public static function trustedCallbacks()
    {
        return ['lazyBuilder'];
    }
}
