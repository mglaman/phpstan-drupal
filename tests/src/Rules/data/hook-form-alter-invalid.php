<?php

namespace mglaman\PHPStanDrupal\Tests\Rules\data;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Invalid form alter hook implementations for testing.
 */
class InvalidFormAlterHooks {

  /**
   * Too few parameters (missing $form_state).
   */
  #[Hook('form_alter')]
  public function tooFewParameters(array &$form): void {
    // Invalid: missing $form_state parameter
  }

  /**
   * First parameter not by reference.
   */
  #[Hook('form_alter')]
  public function formNotByReference(array $form, FormStateInterface $form_state, string $form_id): void {
    // Invalid: $form not by reference
  }

  /**
   * Wrong type for second parameter.
   */
  #[Hook('form_alter')]
  public function wrongFormStateType(array &$form, string $form_state, string $form_id): void {
    // Invalid: $form_state should be FormStateInterface
  }

  /**
   * Wrong type for third parameter.
   */
  #[Hook('form_node_form_alter')]
  public function wrongFormIdType(array &$form, FormStateInterface $form_state, int $form_id): void {
    // Invalid: $form_id should be string
  }

  /**
   * Too many parameters.
   */
  #[Hook('form_alter')]
  public function tooManyParameters(array &$form, FormStateInterface $form_state, string $form_id, string $extra): void {
    // Invalid: too many parameters
  }

  /**
   * Wrong array type hint.
   */
  #[Hook('form_alter')]
  public function wrongFormType(string &$form, FormStateInterface $form_state, string $form_id): void {
    // Invalid: $form should be array
  }

}