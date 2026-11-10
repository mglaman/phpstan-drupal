<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityQuery;

use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

final class EntityQueryExecuteWithoutAccessCheckCountType extends IntegerType
{
    private Type $rangeType;

    public function __construct()
    {
        parent::__construct();
        $this->rangeType = IntegerRangeType::createAllGreaterThanOrEqualTo(0);
    }

    public function describe(VerbosityLevel $level): string
    {
        return $this->rangeType->describe($level);
    }
}
