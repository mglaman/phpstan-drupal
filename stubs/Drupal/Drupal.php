<?php

class Drupal {

    /**
     * @template T of object
     * @param ?class-string<T> $class
     * @return ($class is null ? \Drupal\Core\DependencyInjection\ClassResolverInterface : T)
     */
    public static function classResolver(?string $class = NULL) {

    }
}
