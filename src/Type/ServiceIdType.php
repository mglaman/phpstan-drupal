<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use mglaman\PHPStanDrupal\Drupal\ServiceMapStaticAccessor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

final class ServiceIdType extends \PHPStan\Type\StringType
{
    public function __construct()
    {
        parent::__construct();
    }

    public function describe(VerbosityLevel $level): string
    {
        return 'service-id-string';
    }

    public function accepts(Type $type, bool $strictTypes): TrinaryLogic
    {
        if ($type instanceof CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }

        if ($type instanceof ConstantStringType) {
            $serviceDefinition = ServiceMapStaticAccessor::getInstance()->getService($type->getValue());
            if ($serviceDefinition !== null) {
                return TrinaryLogic::createYes();
            }
            // Some services may be dynamically defined, so return maybe.
            return TrinaryLogic::createMaybe();
        }

        if ($type instanceof self) {
            return TrinaryLogic::createYes();
        }

        if ($type instanceof StringType) {
            return TrinaryLogic::createMaybe();
        }

        return TrinaryLogic::createNo();
    }

    public function isSuperTypeOf(Type $type): TrinaryLogic
    {
        if ($type instanceof ConstantStringType) {
            $serviceDefinition = ServiceMapStaticAccessor::getInstance()->getService($type->getValue());
            if ($serviceDefinition !== null) {
                return TrinaryLogic::createYes();
            }
            // Some services may be dynamically defined, so return maybe.
            return TrinaryLogic::createMaybe();
        }

        if ($type instanceof self) {
            return TrinaryLogic::createYes();
        }

        if ($type instanceof parent) {
            return TrinaryLogic::createMaybe();
        }

        if ($type instanceof CompoundType) {
            return $type->isSubTypeOf($this);
        }

        return TrinaryLogic::createNo();
    }

    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : \PHPStan\Type\Type
    {
        return new self();
    }
}
