<?php

declare(strict_types=1);

/**
 * Fixture for the LegacyHook, LegacyRequirementsHook, and
 * LegacyModuleImplementsAlter ignores in extension.neon.
 *
 * Drupal added these attributes in different minor versions and Drupal 12
 * removes them again, so each is missing on some supported core version.
 * PHPStan must not report "Attribute class ... does not exist." for them.
 */

use Drupal\Core\Hook\Attribute\LegacyHook;
use Drupal\Core\Hook\Attribute\LegacyModuleImplementsAlter;
use Drupal\Core\Hook\Attribute\LegacyRequirementsHook;

#[LegacyHook]
function phpstan_drupal_test_node_presave(): void {}

/**
 * @return array<string, mixed>
 */
#[LegacyRequirementsHook]
function phpstan_drupal_test_requirements(string $phase): array {
    return [];
}

/**
 * @param array<string, mixed> $implementations
 */
#[LegacyModuleImplementsAlter]
function phpstan_drupal_test_module_implements_alter(array &$implementations, string $hook): void {}
