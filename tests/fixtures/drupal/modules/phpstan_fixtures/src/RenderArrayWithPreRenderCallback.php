<?php declare(strict_types=1);

namespace Drupal\phpstan_fixtures;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

final class RenderArrayWithPreRenderCallback implements TrustedCallbackInterface {

    public function staticCallback(): array {
        $build = [
            '#type' => 'link',
            '#url' => Url::fromRoute('<front>'),
            '#title' => 'FooBar',
            '#pre_render' => [[self::class, 'preRenderCallback']]
        ];
        return $build;
    }

    public function functionCallback(): array {
        $build = [
            '#type' => 'link',
            '#url' => Url::fromRoute('<front>'),
            '#title' => 'FooBar',
            '#pre_render' => ['current_time'],
        ];
        return $build;
    }

    public function closureCallback(): array {
        $build = [
            '#type' => 'link',
            '#url' => Url::fromRoute('<front>'),
            '#title' => 'FooBar',
            '#pre_render' => [
                static function(array $element) {
                    return $element;
                }
            ],
        ];
        return $build;
    }


    public static function preRenderCallback(array $element) {
        return $element;
    }

    public static function trustedCallbacks()
    {
        return ['preRenderCallback'];
    }
}
