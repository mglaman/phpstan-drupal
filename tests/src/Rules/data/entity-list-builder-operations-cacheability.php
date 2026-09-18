<?php

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

// Bad: overrides getOperations() without CacheableMetadata.
class MissingCacheabilityGetOperations extends EntityListBuilder
{
    public function getOperations(EntityInterface $entity): array
    {
        return [];
    }
}

// Bad: overrides getDefaultOperations() without CacheableMetadata.
class MissingCacheabilityGetDefaultOperations extends EntityListBuilder
{
    protected function getDefaultOperations(EntityInterface $entity): array
    {
        return [];
    }
}

// Bad: overrides both without CacheableMetadata.
class MissingCacheabilityBoth extends EntityListBuilder
{
    public function getOperations(EntityInterface $entity): array
    {
        return [];
    }

    protected function getDefaultOperations(EntityInterface $entity): array
    {
        return [];
    }
}

// Good: both methods include the CacheableMetadata parameter.
class CorrectCacheabilityBoth extends EntityListBuilder
{
    public function getOperations(EntityInterface $entity, ?CacheableMetadata $cacheability = null): array
    {
        return [];
    }

    protected function getDefaultOperations(EntityInterface $entity, ?CacheableMetadata $cacheability = null): array
    {
        return [];
    }
}

// Not related to EntityListBuilder — no errors expected.
class UnrelatedClass
{
    public function getOperations(EntityInterface $entity): array
    {
        return [];
    }

    public function getDefaultOperations(EntityInterface $entity): array
    {
        return [];
    }
}
