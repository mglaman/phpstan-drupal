<?php

namespace AccessResultType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use function PHPStan\Testing\assertType;

assertType(AccessResultAllowed::class, AccessResult::allowedIf(true));
assertType(AccessResultNeutral::class, AccessResult::allowedIf(false));
