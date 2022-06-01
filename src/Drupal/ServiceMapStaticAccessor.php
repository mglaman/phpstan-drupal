<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\ShouldNotHappenException;

final class ServiceMapStaticAccessor
{
    /**
     * @var \mglaman\PHPStanDrupal\Drupal\ServiceMap
     */
    private static $instance;

    private function __construct()
    {
    }

    public static function registerInstance(ServiceMap $serviceMap): void
    {
        self::$instance = $serviceMap;
    }

    public static function getInstance(): ServiceMap
    {
        if (self::$instance === null) {
            throw new ShouldNotHappenException();
        }

        return self::$instance;
    }
}
