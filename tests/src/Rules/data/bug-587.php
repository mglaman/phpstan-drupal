<?php

namespace Bug587;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Tests the supertype condition on the loadInclude() receiver type.
 *
 * The rule must fire for receivers that are definitely a
 * ModuleHandlerInterface (the concrete class or the interface) and must
 * skip receivers only known as object or mixed.
 */
class TestClass {

    public function bug587(ModuleHandler $a, ModuleHandlerInterface $b, object $c, mixed $d): void
    {
        $a->loadInclude('non_existing_module', 'inc', 'something');
        $b->loadInclude('non_existing_module', 'inc', 'something');
        $c->loadInclude('non_existing_module', 'inc', 'something');
        $d->loadInclude('non_existing_module', 'inc', 'something');
    }

}
