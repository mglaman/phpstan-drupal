<?php declare(strict_types=1);

namespace Drupal\pre_render_callback_rule;

/**
 * Mirrors core's PlaceholderGeneratorTest, which crashed PHPStan (#561).
 */
class PlaceholderGeneratorTest extends PlaceholderTestBase {

    public function build(): array {
        return [
            'listed' => [
                '#lazy_builder' => ['Drupal\pre_render_callback_rule\PlaceholderCallbacks::callback', []],
            ],
            'unlisted' => [
                '#lazy_builder' => ['Drupal\pre_render_callback_rule\PlaceholderCallbacks::unlistedCallback', []],
            ],
        ];
    }

}
