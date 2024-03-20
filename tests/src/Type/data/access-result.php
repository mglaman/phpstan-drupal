<?php

namespace AccessResultType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use function PHPStan\Testing\assertType;

assertType(AccessResultAllowed::class, AccessResult::allowedIf(true));
assertType(AccessResultNeutral::class, AccessResult::allowedIf(false));

assertType(AccessResultForbidden::class, AccessResult::forbiddenIf(true));
assertType(AccessResultNeutral::class, AccessResult::forbiddenIf(false));

$foo = AccessResult::allowedIf('foo');
assertType('???', $foo);
