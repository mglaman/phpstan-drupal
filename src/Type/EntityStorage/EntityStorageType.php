<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityStorage;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class EntityStorageType extends ObjectType
{

    public function __construct(
        private readonly string $entityTypeId,
        string $className,
        ?Type $subtractedType = null,
        ?ClassReflection $classReflection = null
    ) {
        parent::__construct($className, $subtractedType, $classReflection);
    }

    public function getEntityTypeId(): string
    {
        return $this->entityTypeId;
    }

    /**
     * Storages for different entity types are distinct even when they share a
     * class. Without this, a union such as NodeStorage|SqlContentEntityStorage
     * collapses to the base class and the node branch is lost.
     */
    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        if ($type instanceof self && $type->entityTypeId !== $this->entityTypeId) {
            return IsSuperTypeOfResult::createNo();
        }

        return parent::isSuperTypeOf($type);
    }

    public function equals(Type $type): bool
    {
        if ($type instanceof self && $type->entityTypeId !== $this->entityTypeId) {
            return false;
        }

        return parent::equals($type);
    }

    /**
     * PHPStan keys several caches by the cache-level description, so two
     * storages sharing a class need distinct descriptions there. User-facing
     * descriptions are unchanged.
     */
    public function describe(VerbosityLevel $level): string
    {
        $description = parent::describe($level);
        // The level factories return singletons, and the level comparison
        // methods are outside the PHPStan API promise.
        if ($level === VerbosityLevel::cache()) {
            return $description . '#' . $this->entityTypeId;
        }

        return $description;
    }
}
