<?php

namespace Drupal\TestTools\PhpUnitCompatibility\PhpUnit8;

if (class_exists('\Drupal\TestTools\PhpUnitCompatibility\PhpUnit8\ClassWriter')) {
    return;
}

class ClassWriter
{

    public static function mutateTestBase($autoloader) {

    }

}
