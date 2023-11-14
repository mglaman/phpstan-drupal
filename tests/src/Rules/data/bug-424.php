<?php

namespace Bug424;

class Foo {
    public function render(): array {
        $build = [
            'one' => [
                '#lazy_builder' => [
                    static::class . '::contentLazyBuilder',
                    ['foo'],
                ],
                '#create_placeholder' => TRUE,
            ],
            'two' => [
                '#lazy_builder' => [
                    [static::class, 'contentLazyBuilder'],
                    ['foo'],
                ],
                '#create_placeholder' => TRUE,
            ]
        ];

        return $build;
    }

    public static function contentLazyBuilder(string $foo): void {

    }
}
