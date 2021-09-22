<?php

namespace Drupal\phpstan_fixtures\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block with entity.manager injected.
 *
 * @Block(
 *   id = "entity_manager_block",
 * )
 */
class EntityManagerInjectedBlock extends BlockBase implements ContainerFactoryPluginInterface {

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct($configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->entityManager = $entity_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('entity.manager')
        );
    }

    public function build()
    {
        $storage = $this->entityManager->getStorage('node');
        return [];
    }
}
