<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules\data;

use Symfony\Component\DependencyInjection\ContainerInterface;

// No error: core-generated proxy classes fetch the decorated service with a
// dynamic service ID (issue #1012).
class GeneratedProxyLazyLoadingService
{
    protected ContainerInterface $container;

    protected string $drupalProxyOriginalServiceId;

    protected object $service;

    public function __construct(ContainerInterface $container, string $drupal_proxy_original_service_id)
    {
        $this->container = $container;
        $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
    }

    protected function lazyLoadItself(): object
    {
        if (!isset($this->service)) {
            $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
        }

        return $this->service;
    }
}
