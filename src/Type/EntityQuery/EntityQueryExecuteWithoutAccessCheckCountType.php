<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityQuery;

use PHPStan\Type\IntegerType;
use PHPStan\Type\VerbosityLevel;

final class EntityQueryExecuteWithoutAccessCheckCountType extends IntegerType
{

    public function describe(VerbosityLevel $level = null): string
    {
        return 'int<0, max>';
    }
}
