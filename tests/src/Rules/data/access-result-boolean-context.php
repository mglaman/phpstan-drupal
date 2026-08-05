<?php

declare(strict_types=1);

namespace AccessResultBooleanContext;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;

function returnsAccessResult(bool $condition): AccessResultInterface
{
    return AccessResult::allowedIf($condition);
}

function test(AccessResultInterface $access): void
{
    if (!$access) {
    }
    if ($access) {
    }
    $cast = (bool) $access;
    $and = $access && returnsAccessResult(true);
    $or = returnsAccessResult(false) || $access;
    $ternary = $access ? 'a' : 'b';
    while ($access) {
        break;
    }
    if (empty($access)) {
    }
    // Valid usages below must not be reported.
    if (!$access->isAllowed()) {
    }
    if ($access->isForbidden()) {
    }
    $ok = $access->isAllowed() && $access->isNeutral();
}
