<?php declare(strict_types=1);

namespace PHPStan\Drupal;

class ServiceMapFactory implements ServiceMapFactoryInterface
{
    /**
     * @var array
     */
    private $drupalServices;

    public function __construct(array $drupalServiceMap = [])
    {
        $this->drupalServices = $drupalServiceMap;
    }

    public function create(): ServiceMap
    {
        return new ServiceMap($this->drupalServices);
    }
}
