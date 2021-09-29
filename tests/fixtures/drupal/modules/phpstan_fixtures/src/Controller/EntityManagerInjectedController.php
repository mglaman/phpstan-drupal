<?php

namespace Drupal\phpstan_fixtures\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityManagerInjectedController implements ContainerInjectionInterface {

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entity_manager)
    {
        $this->entityManager = $entity_manager;
    }

    public static function create(ContainerInterface $container)
    {
        return new self(
            $container->get('entity.manager')
        );
    }

    public function handle() {
        $storage = $this->entityManager->getStorage('node');
        return [];
    }
}
