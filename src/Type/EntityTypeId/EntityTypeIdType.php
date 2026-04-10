<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityTypeId;

use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class EntityTypeIdType extends StringType
{
    /**
     * @param list<string> $entityTypeIds
     */
    public function __construct(public readonly array $entityTypeIds)
    {
        parent::__construct();
    }

    public function describe(VerbosityLevel $level): string
    {
        return 'entity-type-id';
    }

    public function describeAdditionalCacheKey(): string
    {
        return md5(implode(',', $this->entityTypeIds));
    }

    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return IsSuperTypeOfResult::createYes();
        }
        $constantStrings = $type->getConstantStrings();
        foreach ($constantStrings as $constantString) {
            if (!in_array($constantString->getValue(), $this->entityTypeIds, true)) {
                return IsSuperTypeOfResult::createNo();
            }
        }
        if ($constantStrings !== []) {
            return IsSuperTypeOfResult::createYes();
        }
        if ($type->isString()->yes()) {
            return IsSuperTypeOfResult::createMaybe();
        }
        return parent::isSuperTypeOf($type);
    }
}
