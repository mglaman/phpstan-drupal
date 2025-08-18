<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityQuery;

use PHPStan\Type\IntegerRangeType;

final class EntityQueryExecuteWithoutAccessCheckCountType extends IntegerRangeType
{

    public function __construct()
    {
        // Initialize as a non-negative integer range (0 to max)
        parent::__construct(0, null);
    }
}
