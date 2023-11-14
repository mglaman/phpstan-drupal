<?php

namespace SymfonyCmfRoutingUsage;

class Foo {
    public const NAME = \Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_NAME;
    public const OBJECT = \Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT;
    public const CONTROLLER = \Symfony\Cmf\Component\Routing\RouteObjectInterface::CONTROLLER_NAME;
    public const UNMAPPED = \Symfony\Cmf\Component\Routing\RouteObjectInterface::TEMPLATE_NAME;
    public function a(\Symfony\Cmf\Component\Routing\RouteObjectInterface $object) {

    }
    public function b(\Symfony\Cmf\Component\Routing\RouteProviderInterface $provider): \Symfony\Cmf\Component\Routing\RouteObjectInterface {

    }
    public function c(\Symfony\Cmf\Component\Routing\LazyRouteCollection $collection) {

    }
    public function getIterator(): \Traversable {
        return new \ArrayIterator([]);
    }
}
class Bar {
    public const NAME = \Drupal\Core\Routing\RouteObjectInterface::ROUTE_NAME;
    public const OBJECT = \Drupal\Core\Routing\RouteObjectInterface::ROUTE_OBJECT;
    public const CONTROLLER = \Drupal\Core\Routing\RouteObjectInterface::CONTROLLER_NAME;
    public function a(\Drupal\Core\Routing\RouteObjectInterface $object) {

    }
    public function b(\Drupal\Core\Routing\RouteProviderInterface $provider): \Drupal\Core\Routing\RouteObjectInterface {

    }
    public function c(\Drupal\Core\Routing\LazyRouteCollection $collection) {

    }
    public function d(): \Symfony\Component\Routing\RouteCollection {

    }
}
