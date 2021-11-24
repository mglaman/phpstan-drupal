<?php

declare(strict_types=1);

namespace Drupal\phpstan_fixtures;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

final class CustomContentEntityStorage extends ContentEntityStorageBase {
    protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {}
    protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {}
    protected function doLoadRevisionFieldItems($revision_id) {}
    protected function doLoadMultipleRevisionsFieldItems($revision_ids) {}
    protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {}
    protected function doDeleteFieldItems($entities) {}
    protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {}
    protected function doLoadMultiple(array $ids = null) {}
    protected function has($id, EntityInterface $entity) {}
    protected function getQueryServiceName() {}
    public function countFieldData($storage_definition, $as_bool = false) {}
}
