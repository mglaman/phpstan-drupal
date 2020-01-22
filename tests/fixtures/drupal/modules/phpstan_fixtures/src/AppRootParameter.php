<?php declare(strict_types=1);

namespace Drupal\phpstan_fixtures;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class AppRootParameter implements ContainerInjectionInterface {

    private $appRoot;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new self($container->get('app.root'));
    }

    /**
     * AppRootParameter constructor.
     *
     * @param string $app_root
     *   The app root.
     */
    public function __construct(string $app_root)
    {
        $this->appRoot = $app_root;
    }

    public function testUnion(): string {
        return $this->appRoot . '/core';
    }
}
