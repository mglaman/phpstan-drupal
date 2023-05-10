<?php

namespace Bug545;

/**
 * Code snippets from \Drupal\migrate_drupal\Plugin\migrate\source\ContentEntity.
 */
class TestClass {

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;


    /**
     * The entity type definition.
     *
     * @var \Drupal\Core\Entity\EntityTypeInterface
     */
    protected $entityType;

    /**
     * Query to retrieve the entities.
     *
     * @return \Drupal\Core\Entity\Query\QueryInterface
     *   The query.
     */
    public function query() {
        $query = $this->entityTypeManager
            ->getStorage($this->entityType->id())
            ->getQuery()
            ->accessCheck(FALSE);
        if (!empty($this->configuration['bundle'])) {
            $query->condition($this->entityType->getKey('bundle'), $this->configuration['bundle']);
        }
        // Exclude anonymous user account.
        if ($this->entityType->id() === 'user' && !empty($this->entityType->getKey('id'))) {
            $query->condition($this->entityType->getKey('id'), 0, '>');
        }
        return $query;
    }

    protected function doCountInline() {
        $query = $this->entityTypeManager
            ->getStorage($this->entityType->id())
            ->getQuery()
            ->accessCheck(FALSE)
            ->count();
        return $query->execute();
    }

    protected function doQuery() {
        return $this->query()->execute();
    }

    protected function doCount() {
        return $this->query()->count()->execute();
    }

}
