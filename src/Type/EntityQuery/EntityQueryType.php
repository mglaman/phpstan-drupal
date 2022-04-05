<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityQuery;

use PHPStan\Type\ObjectType;

class EntityQueryType extends ObjectType
{
    private bool $hasAccessCheck = false;

    public function hasAccessCheck(): bool
    {
        return $this->hasAccessCheck;
    }

    public function withAccessCheck(): self
    {
        $type = clone $this;
        $type->hasAccessCheck = true;

        return $type;
    }
}
