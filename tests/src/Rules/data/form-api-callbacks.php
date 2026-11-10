<?php

namespace FormApiCallbacks;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

function existing_submit(array &$form, FormStateInterface $form_state): void
{
}

final class Callbacks
{
    public static function staticCallback(): void
    {
    }

    public function instanceCallback(): void
    {
    }

    private static function privateStatic(): void
    {
    }
}

final class MagicCallbacks
{
    public function __call(string $name, array $arguments): void
    {
    }

    public static function __callStatic(string $name, array $arguments): void
    {
    }
}

class ExampleForm extends FormBase
{
    public function getFormId(): string
    {
        return 'form_api_callbacks_example';
    }

    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#submit' => [
                '::submitForm',
                '::missingMethod',
                '::privateHandler',
                'FormApiCallbacks\existing_submit',
                'missing_function',
                [$this, 'submitForm'],
                [$this, 'missingMethod'],
                [static::class, 'ajaxCallback'],
                [Callbacks::class, 'staticCallback'],
                [Callbacks::class, 'instanceCallback'],
                [Callbacks::class, 'missing'],
                [Callbacks::class, 'privateStatic'],
                Callbacks::class . '::staticCallback',
                'FormApiCallbacks\Callbacks::instanceCallback',
                'FormApiCallbacks\Callbacks::missing',
                static::class . '::ajaxCallback',
                'messenger:addStatus',
                'messenger:missingMethod',
                'unknown_service:method',
                [MagicCallbacks::class, 'anything'],
                function (array &$form, FormStateInterface $form_state): void {
                },
                $form_state->get('callback'),
            ],
            '#ajax' => [
                'callback' => '::missingAjax',
                'wrapper' => 'example-wrapper',
            ],
        ];
        $form['#validate'][] = '::missingValidate';
        $form['#validate'][] = 'another_missing_function';
        $form['foo']['#ajax']['callback'] = [static::class, 'ajaxCallback'];
        $form['bar']['#ajax'] = ['callback' => '::ajaxCallback'];
        $form['#process'] = ['::missingProcess'];
        $form['#after_build'][0] = '::ajaxCallback';
        $form['baz']['#element_validate'][] = '::missingElementValidate';
        return $form;
    }

    public function ajaxCallback(array &$form, FormStateInterface $form_state): array
    {
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
    }

    private function privateHandler(array &$form, FormStateInterface $form_state): void
    {
    }

    #[Hook('form_node_form_alter')]
    public function formAlter(array &$form, FormStateInterface $form_state): void
    {
        $form['#submit'][] = '::methodOfTheAlteredForm';
        $form['actions']['submit']['#submit'][] = [static::class, 'ajaxCallback'];
    }
}

abstract class AbstractForm extends FormBase
{
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['#submit'][] = '::definedBySubclass';
        $form['#submit'][] = [$this, 'definedBySubclass'];
        $form['#submit'][] = [static::class, 'definedBySubclass'];
        $form['#submit'][] = [static::class, 'submitForm'];
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
    }
}

final class NotAForm
{
    public function build(): array
    {
        return [
            '#process' => ['::processedOnSomeForm'],
            '#after_build' => [[$this, 'missingMethod']],
        ];
    }
}

function form_api_callbacks_form_alter(array &$form, FormStateInterface $form_state): void
{
    $form['#validate'][] = '::methodOfTheAlteredForm';
    $form['#validate'][] = 'form_api_callbacks_missing_validate';
    $form['actions']['submit']['#submit'][] = [Callbacks::class, 'staticCallback'];
    $form['actions']['submit']['#ajax']['callback'] = [Callbacks::class, 'instanceCallback'];
    $form['#submit'][] = Callbacks::class . ':instanceCallback';
    $form['#submit'][] = Callbacks::class . ':missing';
}

function form_api_callbacks_keyed_alter(array &$form, FormStateInterface $form_state): void
{
    $form['#validate']['form_api_callbacks'] = 'form_api_callbacks_missing_keyed_validate';
}

class StaticProcessForm extends FormBase
{
    public function getFormId(): string
    {
        return 'form_api_callbacks_static_process';
    }

    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
    }

    public static function processWidget(array $element, FormStateInterface $form_state, array &$complete_form): array
    {
        $element['#ajax']['callback'] = '::refreshOnTheBuiltForm';
        return $element + ['#after_build' => ['::afterBuildOnTheBuiltForm']];
    }
}

#[Hook('form_node_form_alter', method: 'alterNodeForm')]
class ClassLevelHookForm extends FormBase
{
    public function getFormId(): string
    {
        return 'form_api_callbacks_class_level_hook';
    }

    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
    }

    public function alterNodeForm(array &$form, FormStateInterface $form_state): void
    {
        $form['#submit'][] = '::methodOfTheAlteredForm';
    }
}

final class Invokable
{
    public function __invoke(array &$form, FormStateInterface $form_state): void
    {
    }
}

final class NotInvokable
{
    public function submit(array &$form, FormStateInterface $form_state): void
    {
    }
}

function form_api_callbacks_invokable_alter(array &$form, FormStateInterface $form_state): void
{
    $form['#submit'][] = Invokable::class;
    $form['#submit'][] = NotInvokable::class;
    $form['#submit'][] = 'messenger';
}

class LateStaticServiceNotation
{
    public function existing(array &$form, FormStateInterface $form_state): void
    {
    }

    public function build(array $form): array
    {
        $form['#submit'][] = static::class . ':existing';
        $form['#submit'][] = static::class . ':missing';
        return $form;
    }
}

abstract class AbstractInvokable
{
    public function __invoke(array &$form, FormStateInterface $form_state): void
    {
    }

    public function run(array &$form, FormStateInterface $form_state): void
    {
    }
}

interface CallbackInterface
{
}

function form_api_callbacks_class_resolver_alter(array &$form, FormStateInterface $form_state): void
{
    $form['#submit'][] = AbstractInvokable::class;
    $form['#submit'][] = CallbackInterface::class;
    $form['#submit'][] = AbstractInvokable::class . ':run';
    $form['#submit'][] = 'service_map.abstract_service_private';
    $form['#submit'][] = 'service_map.abstract_service_private:build';
}

abstract class AbstractLateStaticServiceNotation
{
    public function build(array $form): array
    {
        $form['#submit'][] = static::class . ':definedInSubclass';
        return $form;
    }
}

class LateStaticPrivateMethod
{
    private function privateHandler(array &$form, FormStateInterface $form_state): void
    {
    }

    public function build(array $form): array
    {
        $form['#submit'][] = static::class . ':privateHandler';
        return $form;
    }
}
