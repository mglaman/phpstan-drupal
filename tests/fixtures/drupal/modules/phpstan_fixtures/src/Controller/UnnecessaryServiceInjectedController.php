<?php

namespace Drupal\phpstan_fixtures\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UnnecessaryServiceInjectedController extends ControllerBase
{

    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var \Drupal\Core\Cache\CacheBackendInterface
     */
    protected $cache;

    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        CacheBackendInterface $cache_backend
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->cache = $cache_backend;
    }

    public static function create(ContainerInterface $container)
    {
        return new self(
            $container->get('entity_type.manager'),
            $container->get('cache.default')
        );
    }

    public function handle()
    {
        return [];
    }
}
