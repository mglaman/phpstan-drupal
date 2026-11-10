<?php

declare(strict_types=1);

namespace DrupalEntityTypeId;

use function PHPStan\Testing\assertType;

/**
 * @param entity-type-id $entityTypeId
 */
function acceptsEntityTypeId(string $entityTypeId): void
{
    assertType('entity-type-id', $entityTypeId);
}

/**
 * @return entity-type-id
 */
function returnsEntityTypeId(): string
{
    return 'node';
}

assertType('entity-type-id', returnsEntityTypeId());
