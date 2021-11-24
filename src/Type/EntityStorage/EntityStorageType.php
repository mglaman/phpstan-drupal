<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityStorage;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class EntityStorageType extends ObjectType
{
    /**
     * @var string
     */
    private $entityTypeId;

    public function __construct(
        string $entityTypeId,
        string $className,
        ?Type $subtractedType = null,
        ?ClassReflection $classReflection = null
    ) {
        parent::__construct($className, $subtractedType, $classReflection);

        $this->entityTypeId = $entityTypeId;
    }

    public function getEntityTypeId(): string
    {
        return $this->entityTypeId;
    }
}
