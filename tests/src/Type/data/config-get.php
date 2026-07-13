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

    // system.mail.interface is a sequence; 'default' is a dynamic element key -> string|null
    assertType('string|null', \Drupal::config('system.mail')->get('interface.default'));

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

function testDynamicTypeReference(): void {
    // mailer_dsn.scheme is a static string in the schema.
    assertType('string|null', \Drupal::config('system.mail')->get('mailer_dsn.scheme'));

    // mailer_dsn.options uses the dynamic type
    // `mailer_dsn.options.[%parent.scheme]` — no narrowing possible.
    assertType('mixed', \Drupal::config('system.mail')->get('mailer_dsn.options'));
    assertType('mixed', \Drupal::config('system.mail')->get('mailer_dsn.options.verify_peer'));
}

function testPartiallyResolvableUnion(bool $flag): void {
    // One branch resolves, the other does not: narrowing must not drop the
    // unresolvable branch.
    $key = $flag ? 'message' : 'unknown_key_xyz';
    assertType('mixed', \Drupal::config('system.maintenance')->get($key));

    // Both branches resolve: union of both types.
    $key = $flag ? 'message' : 'langcode';
    assertType('string|null', \Drupal::config('system.maintenance')->get($key));
}

class NotDrupal {
    public static function config(string $name): ImmutableConfig {
        return \Drupal::config('some.other_config');
    }
}

class HasConfigHelper {
    public function config(string $name): ImmutableConfig {
        return \Drupal::config('mymodule.' . $name);
    }

    public function run(): void {
        // Not ConfigFormBaseTrait — no narrowing.
        assertType('mixed', $this->config('system.maintenance')->get('message'));
    }
}

function testStaticCallOnOtherClass(): void {
    // Static ::config() on a class other than \Drupal — no narrowing.
    assertType('mixed', NotDrupal::config('system.maintenance')->get('message'));
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
