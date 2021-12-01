<?php

declare(strict_types=1);

namespace fixtures\drupal\modules\phpstan_fixtures\src\Internal;

use Drupal\module_with_internal_classes\Foo\InternalClass;

final class WithAnAnonymousClassExtendingAnInternalClass
{
    public function foo(): void
    {
        $foo = new class() extends InternalClass {};
    }
}
