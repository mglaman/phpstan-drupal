<?php

namespace MultisiteLoadInclude;

/**
 * Tests that loadInclude works for modules in non-default site directories.
 *
 * This demonstrates a bug where ExtensionDiscovery only scanned sites/default,
 * causing modules under other site directories (e.g. sites/acme/)
 * to not be found.
 */
function test_multisite_load_include(): void {
  \Drupal::moduleHandler()->loadInclude('acme_module', 'inc');
}
