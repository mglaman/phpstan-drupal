<?php declare(strict_types=1);

namespace PHPStan\Drupal;

interface ServiceMapFactoryInterface
{
    public function create(): ServiceMap;
}
