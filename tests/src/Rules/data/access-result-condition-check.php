<?php

namespace AccessResultConditionCheck;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;

function foo(bool $x): AccessResultInterface
{
    return AccessResult::allowedIf($x);
}
function bar(): AccessResultInterface
{
    return AccessResult::allowedIf(false === false);
}
function baz(): AccessResultInterface
{
    return AccessResult::allowedIf(false !== false);
}
