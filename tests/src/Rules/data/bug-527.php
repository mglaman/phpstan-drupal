<?php

namespace Bug527;

use Drupal\Core\Security\Attribute\TrustedCallback;

class Foo {
    #[TrustedCallback]
    public static function someTrustedCallback() {

    }

    public static function someCallback() {

    }

    public function render(): array {
        return [
            'one' => [
                '#lazy_builder' => [
                    [self::class, 'someTrustedCallback'],
                    ['bar']
                ]
            ],
            'two' => [
                '#lazy_builder' => [
                    [self::class, 'someCallback'],
                    ['bar']
                ]
            ]
        ];
    }
}
