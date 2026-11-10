<?php

namespace Drupal\phpstan_fixtures;

class UsesDeprecatedUrlFunction {
    public function test() {
        $url = \Drupal::url('fake_route');
    }
}
