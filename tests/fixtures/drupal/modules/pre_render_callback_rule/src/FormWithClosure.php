<?php declare(strict_types=1);

namespace Drupal\pre_render_callback_rule;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

final class FormWithClosure implements FormInterface, TrustedCallbackInterface {

    public static function preRenderCallback(array $element) {
        return $element;
    }

    public static function trustedCallbacks()
    {
        return ['preRenderCallback'];
    }

    public function getFormId()
    {
        // TODO: Implement getFormId() method.
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        return [
            '#type' => 'link',
            '#url' => Url::fromRoute('<front>'),
            '#title' => 'FooBar',
            '#pre_render' => [
                [self::class, 'preRenderCallback'],
                [$this, 'preRenderCallback'],
                static function(array $element): array {
                    return $element;
                }
            ]
        ];
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        // TODO: Implement validateForm() method.
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // TODO: Implement submitForm() method.
    }
}
