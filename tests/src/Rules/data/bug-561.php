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

abstract class AbstractBase implements TrustedCallbackInterface {

    public function build(): array {
        return [
            '#pre_render' => [[$this, 'preRender']],
        ];
    }

    public function preRender(array $element): array {
        return $element;
    }

}

class SubclassListsMethod extends AbstractBase {

    public static function trustedCallbacks() {
        return ['preRender'];
    }

}

class SubclassMissesMethod extends AbstractBase {

    public static function trustedCallbacks() {
        return [];
    }

}

class SubclassUsesAttribute extends AbstractBase {

    #[TrustedCallback]
    public function preRender(array $element): array {
        return $element;
    }

    public static function trustedCallbacks() {
        return [];
    }

}

abstract class AbstractBaseWithoutSubclasses implements TrustedCallbackInterface {

    public function build(): array {
        return [
            '#pre_render' => [[$this, 'preRender']],
        ];
    }

    public function preRender(array $element): array {
        return $element;
    }

    public static function trustedCallbacks() {
        return [];
    }

}

class AppendedCallbacks implements TrustedCallbackInterface {

    public function build(): array {
        return [
            '#pre_render' => [[$this, 'appended'], [$this, 'unlisted']],
        ];
    }

    public function appended(array $element): array {
        return $element;
    }

    public function unlisted(array $element): array {
        return $element;
    }

    public static function trustedCallbacks() {
        $callbacks = ['build'];
        $callbacks[] = 'appended';
        return $callbacks;
    }

}

class MergedCallbacks extends ListedCallbacks {

    public static function trustedCallbacks() {
        return array_merge(parent::trustedCallbacks(), ['other']);
    }

}

class ConditionalCallbacks implements TrustedCallbackInterface {

    public static function first(): array {
        return [];
    }

    public static function second(): array {
        return [];
    }

    public static function unlisted(): array {
        return [];
    }

    public static function trustedCallbacks() {
        if (random_int(0, 1) === 1) {
            return ['first'];
        }
        return ['second'];
    }

}

trait CallbackSiteTrait {

    public function build(): array {
        return [
            '#pre_render' => [[$this, 'traitCallback']],
        ];
    }

    public function traitCallback(array $element): array {
        return $element;
    }

}

class TraitSiteListsMethod implements TrustedCallbackInterface {

    use CallbackSiteTrait;

    public static function trustedCallbacks() {
        return ['traitCallback'];
    }

}

class TraitSiteMissesMethod implements TrustedCallbackInterface {

    use CallbackSiteTrait;

    public static function trustedCallbacks() {
        return [];
    }

}

class MoreUsage {

    public function build(): array {
        return [
            'merged' => ['#pre_render' => [MergedCallbacks::class . '::unlisted']],
            'conditional_first' => ['#pre_render' => [ConditionalCallbacks::class . '::first']],
            'conditional_second' => ['#pre_render' => [ConditionalCallbacks::class . '::second']],
            'conditional_unlisted' => ['#pre_render' => [ConditionalCallbacks::class . '::unlisted']],
            'abstract_named' => ['#pre_render' => [AbstractBaseWithoutSubclasses::class . '::preRender']],
        ];
    }

}

class ViewBuilderBase implements TrustedCallbackInterface {

    public function viewMultiple(): array {
        return [
            '#pre_render' => [[$this, 'buildMultiple']],
        ];
    }

    public function buildMultiple(array $element): array {
        return $element;
    }

    public static function trustedCallbacks() {
        return ['buildMultiple'];
    }

}

class ViewBuilderOverridesSite extends ViewBuilderBase {

    public function viewMultiple(): array {
        return [];
    }

    public static function trustedCallbacks() {
        return [];
    }

}

class ViewBuilderInheritsSite extends ViewBuilderBase {

    public static function trustedCallbacks() {
        return [];
    }

}

abstract class CallbackServiceBase implements TrustedCallbackInterface {

    public function preRender(array $element): array {
        return $element;
    }

}

class CallbackServiceListsMethod extends CallbackServiceBase {

    public static function trustedCallbacks() {
        return ['preRender'];
    }

}

class CallbackServiceMissesMethod extends CallbackServiceBase {

    public static function trustedCallbacks() {
        return [];
    }

}

class CallbackServiceConsumer {

    public function __construct(
        private CallbackServiceBase $baseService,
        private CallbackServiceMissesMethod $concreteService,
    ) {
    }

    public function build(): array {
        return [
            '#pre_render' => [
                [$this->baseService, 'preRender'],
                [$this->concreteService, 'preRender'],
            ],
        ];
    }

}
