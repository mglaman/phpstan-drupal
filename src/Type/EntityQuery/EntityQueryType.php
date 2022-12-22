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
        // The constructor of ObjectType is under backward compatibility promise.
        // @see https://phpstan.org/developing-extensions/backward-compatibility-promise
        // @phpstan-ignore-next-line
        $type = new static(
            $this->getClassName(),
            $this->getSubtractedType(),
            $this->getClassReflection()
        );
        $type->hasAccessCheck = true;
        return $type;
    }

    protected function describeAdditionalCacheKey(): string
    {
        return $this->hasAccessCheck ? 'with-access-check' : 'without-access-check';
    }
}
