<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityTypeId;

use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class EntityTypeIdType extends StringType
{
    /**
     * @var list<string>
     */
    private array $entityTypeIds;

    /**
     * @param list<string> $entityTypeIds
     */
    public function __construct(array $entityTypeIds)
    {
        $this->entityTypeIds = $entityTypeIds;
    }

    /**
     * @return list<string>
     */
    public function getEntityTypeIds(): array
    {
        return $this->entityTypeIds;
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
        if ($type instanceof ConstantStringType) {
            return IsSuperTypeOfResult::createFromBoolean(
                in_array($type->getValue(), $this->entityTypeIds, true)
            );
        }
        if ($type instanceof StringType) {
            return IsSuperTypeOfResult::createMaybe();
        }
        return parent::isSuperTypeOf($type);
    }
}
