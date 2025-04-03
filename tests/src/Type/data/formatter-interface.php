<?php

namespace DrupalFormatterInterface;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Form\FormStateInterface;
use function PHPStan\Testing\assertType;

/**
 * @implements \Drupal\Core\Field\FormatterInterface<\Drupal\Core\Field\Plugin\Field\FieldType\StringItem>
 */
class StringFormatter implements FormatterInterface {

    public function settingsForm(array $form, FormStateInterface $form_state) {
        return [];
    }

    public function settingsSummary() {
        return [];
    }

    public function prepareView(array $entities_items) {
        $first_item = $entities_items[0]->first();
        // Assert that the first item in the list is a StringItem
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $first_item);
    }

    public function view(FieldItemListInterface $items, $langcode = NULL) {
        assertType('Drupal\Core\Field\FieldItemListInterface<Drupal\Core\Field\Plugin\Field\FieldType\StringItem>', $items);
        $first_item = $items->first();
        // Assert that the first item in the list is a StringItem
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $first_item);
        return [];
    }

    public function viewElements(FieldItemListInterface $items, $langcode) {
        $first_item = $items->first();
        // Assert that the first item in the list is a StringItem
        assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $first_item);
        return [];
    }

    public static function isApplicable(FieldDefinitionInterface $field_definition) {
        return true;
    }

    public function getSettings() {
        return [];
    }

    public function getSetting($key) {
        return null;
    }

    public function setSetting($key, $value) {
        return $this;
    }

    public function setSettings(array $settings) {
        return $this;
    }

    public function getDefaultSettings() {
        return [];
    }

    public function getPluginId() {
        return 'string_formatter';
    }

    public function getPluginDefinition() {
        return [];
    }

    public function getThirdPartySettings($provider = NULL) {
        return [];
    }

    public function getThirdPartySetting($provider, $key, $default = NULL) {
        return $default;
    }

    public function setThirdPartySetting($provider, $key, $value) {
        return $this;
    }

    public function unsetThirdPartySetting($provider, $key) {
        return $this;
    }

    public function getThirdPartyProviders() {
        return [];
    }
}

/**
 * @template T of \Drupal\Core\Field\FieldItemInterface
 * @extends \Drupal\Core\Field\FormatterBase<T>
 */
class GenericExtendsFormatterBase extends FormatterBase {
    /**
     * @param \Drupal\Core\Field\FieldItemListInterface<T> $items
     */
    public function viewElements(FieldItemListInterface $items, $langcode) {
        $first = $items->first();
        assertType('T of Drupal\Core\Field\FieldItemInterface (class DrupalFormatterInterface\GenericExtendsFormatterBase, argument)|null', $first);
        return [];
    }

    public static function isApplicable(FieldDefinitionInterface $field_definition) {
        return true;
    }
}

/**
 * @template T of \Drupal\Core\Field\FieldItemInterface
 */
class GenericFormatter implements FormatterInterface {
    public function settingsForm(array $form, FormStateInterface $form_state) {
        return [];
    }

    public function settingsSummary() {
        return [];
    }

    public function prepareView(array $entities_items) {}

    /**
     * @param \Drupal\Core\Field\FieldItemListInterface<T> $items
     */
    public function view(FieldItemListInterface $items, $langcode = NULL) {
        $first = $items->first();
        assertType('T of Drupal\Core\Field\FieldItemInterface (class DrupalFormatterInterface\GenericFormatter, argument)|null', $first);
        return [];
    }

    /**
     * @param \Drupal\Core\Field\FieldItemListInterface<T> $items
     */
    public function viewElements(FieldItemListInterface $items, $langcode) {
        $first = $items->first();
        assertType('T of Drupal\Core\Field\FieldItemInterface (class DrupalFormatterInterface\GenericFormatter, argument)|null', $first);
        return [];
    }

    public static function isApplicable(FieldDefinitionInterface $field_definition) {
        return true;
    }

    public function getSettings() {
        return [];
    }

    public function getSetting($key) {
        return null;
    }

    public function setSetting($key, $value) {
        return $this;
    }

    public function setSettings(array $settings) {
        return $this;
    }

    public function getDefaultSettings() {
        return [];
    }

    public function getPluginId() {
        return 'generic_formatter';
    }

    public function getPluginDefinition() {
        return [];
    }

    public function getThirdPartySettings($provider = NULL) {
        return [];
    }

    public function getThirdPartySetting($provider, $key, $default = NULL) {
        return $default;
    }

    public function setThirdPartySetting($provider, $key, $value) {
        return $this;
    }

    public function unsetThirdPartySetting($provider, $key) {
        return $this;
    }

    public function getThirdPartyProviders() {
        return [];
    }
}

/**
 * Test usage of the formatter through a function.
 *
 * @param \Drupal\Core\Field\FormatterInterface<\Drupal\Core\Field\Plugin\Field\FieldType\StringItem> $formatter
 * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\Plugin\Field\FieldType\StringItem> $items
 */
function testFormatterWithStringItems(FormatterInterface $formatter, FieldItemListInterface $items): void {
    $elements = $formatter->viewElements($items, 'en');
    assertType('array<int, array<int|string, mixed>>', $elements);

    // Test with implementation
    $stringFormatter = new StringFormatter();
    $elements = $stringFormatter->viewElements($items, 'en');
    assertType('array<int, array<int|string, mixed>>', $elements);

    $item = $items->first();
    // The generic type from FormatterInterface should flow to $item
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $item);
}

/**
 * Test that FormatterBase correctly implements FormatterInterface with generics.
 *
 * @param \Drupal\Core\Field\FormatterBase<\Drupal\Core\Field\Plugin\Field\FieldType\StringItem> $formatter
 * @param \Drupal\Core\Field\FieldItemListInterface<\Drupal\Core\Field\Plugin\Field\FieldType\StringItem> $items
 */
function testFormatterBaseWithStringItems(FormatterBase $formatter, FieldItemListInterface $items): void {
    $output = $formatter->view($items, 'en');
    assertType('array<int|string, mixed>', $output);

    $item = $items->first();
    // The generic type from FormatterBase should flow to $item
    assertType('Drupal\Core\Field\Plugin\Field\FieldType\StringItem|null', $item);
}
