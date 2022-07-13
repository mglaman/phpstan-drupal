<?php

namespace DrupalRequestStackShim;

class Foo {
    public function __construct(\Symfony\Component\HttpFoundation\RequestStack $stack)
    {
    }
    public function getStack(): \Symfony\Component\HttpFoundation\RequestStack
    {
        return new \Symfony\Component\HttpFoundation\RequestStack();
    }
}
class Bar {
    public function __construct(\Drupal\Core\Http\RequestStack $stack)
    {
    }
    public function getStack(): \Drupal\Core\Http\RequestStack
    {
        return new \Drupal\Core\Http\RequestStack();
    }
}
