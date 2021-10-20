<?php declare(strict_types=1);

namespace Drupal\pre_render_callback_rule;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

final class RenderArrayWithPreRenderCallback implements TrustedCallbackInterface {

    public function staticCallback(): array {
        return [
            '#type' => 'link',
            '#url' => Url::fromRoute('<front>'),
            '#title' => 'FooBar',
            '#pre_render' => [[self::class, 'preRenderCallback']]
        ];
    }

    public static function preRenderCallback(array $element) {
        return $element;
    }

    public static function trustedCallbacks()
    {
        return ['preRenderCallback'];
    }
}
