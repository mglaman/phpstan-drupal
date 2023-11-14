<?php declare(strict_types=1);

namespace Drupal\pre_render_callback_rule;

use Drupal\Core\Render\Element\RenderCallbackInterface;
use Drupal\Core\Url;

final class RenderCallbackInterfaceObject implements RenderCallbackInterface {

    public function staticCallback(): array {
        return [
            '#type' => 'link',
            '#url' => Url::fromRoute('<front>'),
            '#title' => 'FooBar',
            '#pre_render' => [
                [$this, 'preRenderCallback'],
            ]
        ];
    }

    public static function preRenderCallback(array $element) {
        return $element;
    }

}
