<?php

declare(strict_types=1);

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\Core\Hook\Attribute\LegacyModuleImplementsAlter;
use Drupal\Core\Hook\Attribute\LegacyRequirementsHook;
use function PHPStan\Testing\assertType;

#[LegacyHook]
function my_module_node_presave(): void {}

#[LegacyRequirementsHook]
function my_module_requirements(string $phase): array { return []; }

#[LegacyModuleImplementsAlter]
function my_module_module_implements_alter(array &$implementations, string $hook): void {}

assertType('null', my_module_node_presave());
assertType('array', my_module_requirements('runtime'));
