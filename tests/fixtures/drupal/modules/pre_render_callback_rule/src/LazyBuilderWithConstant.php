<?php declare(strict_types=1);

namespace Drupal\pre_render_callback_rule;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

class LazyBuilderWithConstant implements TrustedCallbackInterface {

    public function staticCallback(): array {
        return [
            '#lazy_builder' => [
                [[self::class, 'lazyBuilder'], ['baz', true]],
                [[static::class, 'lazyBuilder'], ['mars', false]],
                [[$this, 'lazyBuilder'], ['mars', false]],
                [self::class . '::lazyBuilder', ['baz', true]],
                [static::class . '::lazyBuilder', ['mars', false]],
            ]
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
