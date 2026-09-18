<?php

namespace ConfigGetReturnTypeSystemMail;

use function PHPStan\Testing\assertType;

// These asserts require system.mail to be FullyValidatable, which is not the
// case in every supported Drupal core version. The test gates this file on
// the actual schema content.

function testSystemMail(): void {
    // system.mail has a sequence of strings
    assertType('array<string>|null', \Drupal::config('system.mail')->get('interface'));

    // system.mail.interface is a sequence; 'default' is a dynamic element key -> string|null
    assertType('string|null', \Drupal::config('system.mail')->get('interface.default'));
}

function testDynamicTypeReference(): void {
    // mailer_dsn.scheme is a static string in the schema.
    assertType('string|null', \Drupal::config('system.mail')->get('mailer_dsn.scheme'));

    // mailer_dsn.options uses the dynamic type
    // `mailer_dsn.options.[%parent.scheme]` — no narrowing possible.
    assertType('mixed', \Drupal::config('system.mail')->get('mailer_dsn.options'));
    assertType('mixed', \Drupal::config('system.mail')->get('mailer_dsn.options.verify_peer'));
}
