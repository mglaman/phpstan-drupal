<?php

namespace DrupalRequestStackShim;

use Symfony\Component\HttpFoundation\Request;

class Foo {
    public function __construct(\Symfony\Component\HttpFoundation\RequestStack $stack)
    {
    }
    public function getStack(): \Symfony\Component\HttpFoundation\RequestStack
    {
        return new \Symfony\Component\HttpFoundation\RequestStack();
    }

    public function a(): ?Request {
        return $this->getStack()->getMasterRequest();
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
    public function a(): ?Request {
        return $this->getStack()->getMasterRequest();
    }
    public function b(): ?Request {
        return $this->getStack()->getMainRequest();
    }
}
