<?php

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Deprecations\DeprecatedHookImplementation;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;

/**
 * Test the rule to detected deprecated hook implementations.
 */
class DeprecatedHookImplementationTest extends DrupalRuleTestCase {

    /**
     * {@inheritdoc}
     */
    protected function getRule(): Rule {
        return new DeprecatedHookImplementation(
            self::getContainer()->getByType(ReflectionProvider::class)
        );
    }

    /**
     * Ensure hook deprecations are flagged with and without reason.
     */
    public function testRule() : void {
        $this->analyse([__DIR__ . '/../../fixtures/drupal/modules/module_with_deprecated_hooks/module_with_deprecated_hooks.module'], [
        [
                'Function module_with_deprecated_hooks_example implements hook_example which is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use hook_other_example instead.',
                3,
            ],
            [
                'Function module_with_deprecated_hooks_example2 implements hook_example2 which is deprecated.',
                5,
            ],
        ]);
        [$version] = explode('.', \Drupal::VERSION, 2);
        if ($version === '9') {
            $this->analyse([__DIR__ . '/data/deprecated_field_widget_hooks.module'],
                [
                    [
                        'Function deprecated_field_widget_hooks_field_widget_form_alter implements hook_field_widget_form_alter which is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use
  hook_field_widget_single_element_form_alter instead.',
                        5
                    ],
                    [
                        'Function deprecated_field_widget_hooks_field_widget_textfield_form_alter implements hook_field_widget_WIDGET_TYPE_form_alter which is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use hook_field_widget_single_element_WIDGET_TYPE_form_alter instead.',
                        9
                    ]
                ]);
        }
    }

}
