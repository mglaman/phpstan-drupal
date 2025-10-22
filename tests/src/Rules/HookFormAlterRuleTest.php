<?php

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\HookFormAlterRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

/**
 * Test the HookFormAlterRule.
 */
class HookFormAlterRuleTest extends DrupalRuleTestCase {

    /**
     * {@inheritdoc}
     */
    protected function getRule(): Rule {
        return new HookFormAlterRule();
    }

    /**
     * Test valid form alter hook implementations.
     */
    public function testValidImplementations(): void {
        $this->analyse([
            __DIR__ . '/data/hook-form-alter-valid.php',
        ], [
            // No errors expected for valid implementations
        ]);
    }

    /**
     * Test invalid method-based hook implementations.
     */
    public function testInvalidMethodImplementations(): void {
        $this->analyse([
            __DIR__ . '/data/hook-form-alter-invalid.php',
        ], [
            [
                'Form alter hook "form_alter" implementation must have 2 or 3 parameters. Expected signature: method(&$form, \Drupal\Core\Form\FormStateInterface $form_state[, $form_id])',
                16,
            ],
            [
                'Form alter hook "form_alter" first parameter must be passed by reference (&$form). Expected signature: method(&$form, \Drupal\Core\Form\FormStateInterface $form_state[, $form_id])',
                24,
            ],
            [
                'Form alter hook "form_alter" second parameter should be \Drupal\Core\Form\FormStateInterface, string given. Expected signature: method(&$form, \Drupal\Core\Form\FormStateInterface $form_state[, $form_id])',
                32,
            ],
            [
                'Form alter hook "form_node_form_alter" third parameter should be string, int given. Expected signature: method(&$form, \Drupal\Core\Form\FormStateInterface $form_state[, $form_id])',
                40,
            ],
            [
                'Form alter hook "form_alter" implementation must have 2 or 3 parameters. Expected signature: method(&$form, \Drupal\Core\Form\FormStateInterface $form_state[, $form_id])',
                48,
            ],
            [
                'Form alter hook "form_alter" first parameter should be an array. Expected signature: method(&$form, \Drupal\Core\Form\FormStateInterface $form_state[, $form_id])',
                56,
            ],
        ]);
    }


}