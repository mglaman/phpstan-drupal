<?php

namespace PHPUnit\Runner;

if (class_exists('\PHPUnit\Runner\Version')) {
    return;
}

final class Version {

    public static function id() {
        if (version_compare('9.0.0', \Drupal::VERSION) === 1) {
            return '7.5.20';
        }

        return '9.5.10';
    }

}
