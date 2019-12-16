<?php
namespace Drupal\service_provider_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

class ServiceProviderTestServiceProvider extends ServiceProviderBase {
    public function alter(ContainerBuilder $container)
    {
        if (true) {
            // foo.
        }
    }
}
