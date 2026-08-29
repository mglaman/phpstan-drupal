<?php

declare(strict_types=1);

/**
 * Fixture for verifying LegacyHook, LegacyRequirementsHook, and
 * LegacyModuleImplementsAlter attribute stubs are available regardless of
 * installed Drupal version.
 *
 * These attributes were added at different minor versions and LegacyHook is
 * being removed in Drupal 12. PHPStan should not report "class not found"
 * errors when these attributes are used.
 */

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\Core\Hook\Attribute\LegacyModuleImplementsAlter;
use Drupal\Core\Hook\Attribute\LegacyRequirementsHook;

#[LegacyHook]
function phpstan_drupal_test_node_presave(): void {}

#[LegacyRequirementsHook]
function phpstan_drupal_test_requirements(string $phase): array {
    return [];
}

#[LegacyModuleImplementsAlter]
function phpstan_drupal_test_module_implements_alter(array &$implementations, string $hook): void {}
