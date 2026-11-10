<?php

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

// Bad: entity_operation without CacheableMetadata.
class BadEntityOperationHook
{
    #[Hook('entity_operation')]
    public function entityOperation(EntityInterface $entity): array
    {
        return [];
    }
}

// Bad: entity_operation_alter with only operations param.
class BadEntityOperationAlterHookOneParam
{
    #[Hook('entity_operation_alter')]
    public function entityOperationAlter(array &$operations): void
    {
    }
}

// Bad: entity_operation_alter missing CacheableMetadata (2 params, needs 3).
class BadEntityOperationAlterHookTwoParams
{
    #[Hook('entity_operation_alter')]
    public function entityOperationAlter(array &$operations, EntityInterface $entity): void
    {
    }
}

// Good: entity_operation with non-nullable CacheableMetadata (canonical form).
class GoodEntityOperationHook
{
    #[Hook('entity_operation')]
    public function entityOperation(EntityInterface $entity, CacheableMetadata $cacheability): array
    {
        return [];
    }
}

// Good: entity_operation_alter with non-nullable CacheableMetadata (canonical form).
class GoodEntityOperationAlterHook
{
    #[Hook('entity_operation_alter')]
    public function entityOperationAlter(array &$operations, EntityInterface $entity, CacheableMetadata $cacheability): void
    {
    }
}

// Good: nullable CacheableMetadata also accepted.
class GoodEntityOperationHookNullable
{
    #[Hook('entity_operation')]
    public function entityOperation(EntityInterface $entity, ?CacheableMetadata $cacheability = null): array
    {
        return [];
    }
}

// Good: nullable CacheableMetadata also accepted for alter.
class GoodEntityOperationAlterHookNullable
{
    #[Hook('entity_operation_alter')]
    public function entityOperationAlter(array &$operations, EntityInterface $entity, ?CacheableMetadata $cacheability = null): void
    {
    }
}

// Not an entity operation hook — no errors expected.
class UnrelatedHook
{
    #[Hook('some_other_hook')]
    public function someOtherHook(EntityInterface $entity): array
    {
        return [];
    }
}
