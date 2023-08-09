<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\DeprecatedScope;

use PHPStan\Rules\Deprecations\DeprecatedScopeResolver;

final class DeprecatedScopeFactory
{

    public function groupLegacy(): ?DeprecatedScopeResolver
    {
        if (!interface_exists(DeprecatedScopeResolver::class)) {
            return null;
        }
        return new GroupLegacyScope();
    }

}
