<?php

namespace Bug561;

use Drupal\Core\Render\Element\RenderCallbackInterface;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\Security\TrustedCallbackInterface;

class ListedCallbacks implements TrustedCallbackInterface {

    public static function listed(): array {
        return [];
    }

    public static function unlisted(): array {
        return [];
    }

    #[TrustedCallback]
    public static function unlistedWithAttribute(): array {
        return [];
    }

    public static function trustedCallbacks() {
        return ['listed'];
    }

}

class InheritedCallbacks extends ListedCallbacks {

}

class DynamicCallbacks extends ListedCallbacks {

    public static function trustedCallbacks() {
        $callbacks = parent::trustedCallbacks();
        $callbacks[] = 'unlisted';
        return $callbacks;
    }

}

class RenderCallbacks implements TrustedCallbackInterface, RenderCallbackInterface {

    public static function unlisted(): array {
        return [];
    }

    public static function trustedCallbacks() {
        return [];
    }

}

trait ListedCallbacksTrait {

    public static function trustedCallbacks() {
        return ['listed'];
    }

}

class TraitCallbacks implements TrustedCallbackInterface {

    use ListedCallbacksTrait;

    public static function listed(): array {
        return [];
    }

    public static function unlisted(): array {
        return [];
    }

}

class NonFinalCallbacks implements TrustedCallbackInterface {

    public function build(): array {
        return [
            '#pre_render' => [[$this, 'unlisted']],
        ];
    }

    public function unlisted(array $element): array {
        return $element;
    }

    public static function trustedCallbacks() {
        return [];
    }

}

final class FinalCallbacks implements TrustedCallbackInterface {

    public function build(): array {
        return [
            '#pre_render' => [[$this, 'unlisted'], [$this, 'listed']],
        ];
    }

    public function listed(array $element): array {
        return $element;
    }

    public function unlisted(array $element): array {
        return $element;
    }

    public static function trustedCallbacks() {
        return ['listed'];
    }

}

class Usage {

    public function build(): array {
        return [
            'listed' => ['#pre_render' => [ListedCallbacks::class . '::listed']],
            'unlisted' => ['#pre_render' => [ListedCallbacks::class . '::unlisted']],
            'unlisted_array' => ['#pre_render' => [[ListedCallbacks::class, 'unlisted']]],
            'attribute' => ['#pre_render' => ['Bug561\ListedCallbacks::unlistedWithAttribute']],
            'inherited' => ['#pre_render' => [InheritedCallbacks::class . '::unlisted']],
            'dynamic' => ['#pre_render' => [DynamicCallbacks::class . '::unlisted']],
            'render_callback' => ['#pre_render' => [RenderCallbacks::class . '::unlisted']],
            'trait' => ['#pre_render' => [TraitCallbacks::class . '::unlisted']],
            'lazy_builder' => ['#lazy_builder' => [ListedCallbacks::class . '::unlisted', []]],
            'access_callback' => ['#access_callback' => ListedCallbacks::class . '::unlisted'],
        ];
    }

}
