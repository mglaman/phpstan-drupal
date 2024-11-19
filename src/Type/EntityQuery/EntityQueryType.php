<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityQuery;

use PHPStan\Type\ObjectType;
use function implode;

class EntityQueryType extends ObjectType
{
    private bool $hasAccessCheck = false;

    private bool $isCount = false;

    public function hasAccessCheck(): bool
    {
        return $this->hasAccessCheck;
    }

    public function isCount(): bool
    {
        return $this->isCount;
    }

    public function withAccessCheck(): self
    {
        // The constructor of ObjectType is under backward compatibility promise.
        // @see https://phpstan.org/developing-extensions/backward-compatibility-promise
        // @phpstan-ignore new.static
        $type = new static(
            $this->getClassName(),
            $this->getSubtractedType(),
            $this->getClassReflection()
        );
        $type->hasAccessCheck = true;
        $type->isCount = $this->isCount;
        return $type;
    }

    public function asCount(): self
    {
        // @phpstan-ignore new.static
        $type = new static(
            $this->getClassName(),
            $this->getSubtractedType(),
            $this->getClassReflection()
        );
        $type->hasAccessCheck = $this->hasAccessCheck;
        $type->isCount = true;
        return $type;
    }

    protected function describeAdditionalCacheKey(): string
    {
        $parts = [
            $this->hasAccessCheck ? 'with-access-check' : 'without-access-check',
            $this->isCount ? '' : 'count'
        ];
        return implode('-', $parts);
    }
}
