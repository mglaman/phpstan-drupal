<?php

namespace Bug580;

class Foo {
    public function bar() {
        function baz() {
            \Drupal::a();
        }
    }
}
