<?php

namespace Bug527;

use Drupal\Core\Security\Attribute\TrustedCallback;

class Foo {
    #[TrustedCallback]
    public static function someCallback() {

    }

    public function render(): array {
        return [
            'one' => [
                '#lazy_builder' => [
                    [self::class, 'someCallback'],
                    ['bar']
                ]
            ]
        ];
    }
}
