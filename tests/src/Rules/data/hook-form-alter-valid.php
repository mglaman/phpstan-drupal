<?php

namespace mglaman\PHPStanDrupal\Tests\Rules\data;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Valid form alter hook implementations.
 */
class ValidFormAlterHooks {

  /**
   * Implements hook_form_alter() - method with Hook attribute.
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Valid implementation
  }

  /**
   * Implements hook_form_user_register_form_alter() - method with Hook attribute.
   */
  #[Hook('form_user_register_form_alter')]
  public function userRegisterFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Valid implementation
  }

  /**
   * Implements hook_form_node_form_alter() - method with Hook attribute.
   */
  #[Hook('form_node_form_alter')]
  public function nodeFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Valid implementation
  }

  /**
   * Implements hook_form_alter() - valid with 2 parameters (omitting $form_id).
   */
  #[Hook('form_alter')]
  public function formAlterWithoutFormId(array &$form, FormStateInterface $form_state): void {
    // Valid implementation - $form_id parameter is optional
  }

}