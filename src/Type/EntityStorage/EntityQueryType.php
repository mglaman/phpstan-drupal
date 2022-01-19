<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityStorage;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class EntityQueryType extends ObjectType
{
    private EntityStorageType $entityStorageType;

    public function __construct(
        string $className,
        EntityStorageType $entityStorageType,
        ?Type $subtractedType = null,
        ?ClassReflection $classReflection = null
    ) {
        parent::__construct($className, $subtractedType, $classReflection);
        $this->entityStorageType = $entityStorageType;
    }

    public function getEntityStorageType(): EntityStorageType
    {
        return $this->entityStorageType;
    }
}
