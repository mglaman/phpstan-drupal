<?php

namespace DrupalContainerSyntheticServices;

use Composer\Autoload\ClassLoader;
use Drupal\Core\DrupalKernelInterface;
use function PHPStan\Testing\assertType;

function foo(): void {
    $container = \Drupal::getContainer();
    assertType(DrupalKernelInterface::class, $container->get('kernel'));
    assertType(ClassLoader::class, $container->get('class_loader'));
}
