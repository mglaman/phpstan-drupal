<?php

namespace Bug554;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Tests callbacks for '#date_date_callbacks/' and '#date_time_callbacks'.
 *
 * Code snippets based on \Drupal\KernelTests\Core\Datetime\DatetimeElementFormTest.
 */
class TestClass implements FormInterface, TrustedCallbackInterface {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'test_datelist_element';
    }

    /**
     * {@inheritdoc}
     */
    public function datetimeDateCallbackTrusted(array &$element, FormStateInterface $form_state, DrupalDateTime $date = NULL) {
        $element['datetimeDateCallbackExecuted'] = [
            '#value' => TRUE,
        ];
        $form_state->set('datetimeDateCallbackExecuted', TRUE);
    }

    /**
     * {@inheritdoc}
     */
    public function datetimeTimeCallbackTrusted(array &$element, FormStateInterface $form_state, DrupalDateTime $date = NULL) {
        $element['timeCallbackExecuted'] = [
            '#value' => TRUE,
        ];
        $form_state->set('timeCallbackExecuted', TRUE);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {}

    /**
     * Form validation handler.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {}

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, string $date_callback = 'datetimeDateCallbackTrusted', string $time_callback = 'datetimeTimeCallbackTrusted') {
        $form['datetime_element'] = [
            '#title' => 'datelist test',
            '#type' => 'datetime',
            '#default_value' => new DrupalDateTime('2000-01-01 00:00:00'),
            '#date_date_format' => 'Y-m-d',
            '#date_time_format' => 'H:i:s',
            '#date_date_element' => 'HTML Date',
            '#date_time_element' => 'HTML Time',
            '#date_increment' => 1,
            '#date_date_callbacks' => [[$this, 'datetimeDateCallbackTrusted'], [$this, $date_callback], [$this, 'notExisting']],
            '#date_time_callbacks' => [[$this, 'datetimeTimeCallbackTrusted'], [$this, $time_callback], [$this, 'notExisting']],
        ];

        // Element without specifying the default value.
        $form['simple_datetime_element'] = [
            '#type' => 'datetime',
            '#date_date_format' => 'Y-m-d',
            '#date_time_format' => 'H:i:s',
            '#date_date_element' => 'HTML Date',
            '#date_time_element' => 'HTML Time',
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => t('Submit'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public static function trustedCallbacks() {
        return [
            'datetimeDateCallbackTrusted',
            'datetimeTimeCallbackTrusted',
        ];
    }

}
