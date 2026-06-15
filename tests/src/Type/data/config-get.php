<?php

namespace ConfigGetReturnType;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use function PHPStan\Testing\assertType;

function testDrupalConfig(): void {
    // system.maintenance is FullyValidatable, message is type: text -> string|null
    assertType('string|null', \Drupal::config('system.maintenance')->get('message'));

    // system.cron is FullyValidatable
    assertType('bool|null', \Drupal::config('system.cron')->get('logging'));

    // Nested key: system.cron threshold.requirements_warning is integer
    assertType('int|null', \Drupal::config('system.cron')->get('threshold.requirements_warning'));

    // Both requirements keys exist in the schema
    assertType('int|null', \Drupal::config('system.cron')->get('threshold.requirements_error'));

    // system.theme is NOT FullyValidatable, so should return mixed
    assertType('mixed', \Drupal::config('system.theme')->get('default'));

    // Unknown config should return mixed
    assertType('mixed', \Drupal::config('nonexistent.config')->get('key'));

    // system.mail has a sequence of strings
    assertType('array<string>|null', \Drupal::config('system.mail')->get('interface'));

    // system.feature_flags is FullyValidatable with a boolean
    assertType('bool|null', \Drupal::config('system.feature_flags')->get('linkset_endpoint'));
}

function testConfigFactory(ConfigFactoryInterface $configFactory): void {
    // ConfigFactory::get() returns ImmutableConfig (extends Config), so narrowing applies
    assertType('string|null', $configFactory->get('system.maintenance')->get('message'));
    assertType('int|null', $configFactory->get('system.cron')->get('threshold.requirements_error'));

    // getEditable() returns Config (mutable)
    assertType('string|null', $configFactory->getEditable('system.maintenance')->get('message'));
}

function testImmutableConfig(ImmutableConfig $config): void {
    // ImmutableConfig extends Config, so the extension applies.
    // Without knowing the config name at call site this falls back to mixed.
    assertType('mixed', $config->get('message'));
}

class TestConfigForm extends ConfigFormBase {
    protected function getEditableConfigNames() {
        return ['system.maintenance'];
    }

    public function getFormId() {
        return 'test_config_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        // $this->config() from ConfigFormBaseTrait
        assertType('string|null', $this->config('system.maintenance')->get('message'));
        return $form;
    }
}
