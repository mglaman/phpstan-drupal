<?php

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

// --- FullyValidatable configs: unknown keys should be reported ---

function testUnknownKeysOnFullyValidatable(): void {
    // Valid key — no error expected.
    \Drupal::config('system.maintenance')->get('message');

    // Unknown key on FullyValidatable config — error expected.
    \Drupal::config('system.maintenance')->get('nonexistent_key');

    // Valid nested key — no error.
    \Drupal::config('system.cron')->get('threshold.requirements_warning');

    // Unknown nested key — error expected.
    \Drupal::config('system.cron')->get('threshold.unknown_key');

    // Top-level key that does not exist — error expected.
    \Drupal::config('system.cron')->get('totally_wrong');
}

function testUnknownKeysViaConfigFactory(ConfigFactoryInterface $configFactory): void {
    // Valid key — no error.
    $configFactory->get('system.maintenance')->get('message');

    // Unknown key — error expected.
    $configFactory->get('system.maintenance')->get('bad_key');

    // getEditable — valid key, no error.
    $configFactory->getEditable('system.maintenance')->get('message');

    // getEditable — unknown key, error expected.
    $configFactory->getEditable('system.maintenance')->get('bad_editable_key');
}

// --- Sequence traversal: keys under a sequence should not be reported ---

function testSequenceTraversal(): void {
    // system.mail.interface is a sequence of strings; 'default' is a dynamic
    // element key — should NOT be reported as unknown.
    \Drupal::config('system.mail')->get('interface.default');
}

// --- Non-FullyValidatable configs: no errors regardless of key ---

function testNonFullyValidatable(): void {
    // system.theme is NOT FullyValidatable — no error even for unknown keys.
    \Drupal::config('system.theme')->get('completely_made_up');

    // Unknown config name — no error.
    \Drupal::config('nonexistent.config')->get('any_key');
}

// --- Dynamic type references: keys beneath them are unverifiable ---

function testDynamicTypeReference(): void {
    // system.mail is FullyValidatable, but mailer_dsn.options uses the dynamic
    // type `mailer_dsn.options.[%parent.scheme]` — keys beneath it must not be
    // reported.
    \Drupal::config('system.mail')->get('mailer_dsn.options.verify_peer');
}

// --- Receiver verification: same-named methods on other classes ---

class NotDrupal {
    public static function config(string $name): \Drupal\Core\Config\Config {
        return \Drupal::getContainer()->get('config.factory')->getEditable('some.other_config');
    }
}

class HasConfigHelper {
    public function config(string $name): \Drupal\Core\Config\Config {
        return \Drupal::getContainer()->get('config.factory')->getEditable('mymodule.' . $name);
    }

    public function run(): void {
        // Not ConfigFormBaseTrait; the helper prefixes the name, so the key
        // belongs to a different config — no error.
        $this->config('system.maintenance')->get('anything_at_all');
    }
}

function testStaticCallOnOtherClass(): void {
    // Static ::config() on a class other than \Drupal — no error.
    NotDrupal::config('system.maintenance')->get('legit_key_of_other_config');
}

class TestFormWithUnknownKey extends ConfigFormBase {
    protected function getEditableConfigNames() {
        return ['system.maintenance'];
    }

    public function getFormId() {
        return 'test_form_unknown_key';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        // Valid key — no error.
        $this->config('system.maintenance')->get('message');

        // Unknown key via ConfigFormBaseTrait — error expected.
        $this->config('system.maintenance')->get('unknown_via_trait');

        return $form;
    }
}
