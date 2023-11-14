<?php

namespace Bug500;

enum MyEnum {
    case ONE;
    case TWO;
    public function getTitle(): string {
        return match($this) {
            self::ONE => (string) \Drupal::translation()->translate('One'),
            self::TWO => (string) \Drupal::translation()->translate('Two'),
        };
    }
}
