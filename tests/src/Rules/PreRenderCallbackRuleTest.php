<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;


use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use mglaman\PHPStanDrupal\Rules\Drupal\RenderCallbackRule;

final class PreRenderCallbackRuleTest extends DrupalRuleTestCase {

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new RenderCallbackRule(
            $this->createReflectionProvider()
        );
    }

    /**
     * @dataProvider fileData
     */
    public function testRule(string $path, array $errorMessages): void
    {
        $this->analyse([$path], $errorMessages);
    }

    public function fileData(): \Generator
    {
        yield [
          __DIR__ . '/../../fixtures/drupal/modules/pre_render_callback_rule/pre_render_callback_rule.module',
            [
                [
                    "#pre_render value 'null' at key '0' is invalid.",
                    14
                ],
                [
                    'The "#pre_render" render array value expects an array of callbacks.',
                    18
                ],
                [
                    'The "#pre_render" render array value expects an array of callbacks.',
                    21
                ],
                [
                    "#pre_render callback 'invalid_func' at key '0' is not callable.",
                    26
                ],
                [
                    "#pre_render callback 'sample_pre_render…' at key '1' is not trusted. See https://www.drupal.org/node/2966725.",
                    27
                ],
                [
                    "#pre_render callback class 'Drupal\pre_render_callback_rule\NotTrustedCallback' at key '3' does not implement Drupal\Core\Security\TrustedCallbackInterface.",
                    29
                ],
                [
                    "#pre_render callback class 'Drupal\pre_render_callback_rule\NotTrustedCallback' at key '4' does not implement Drupal\Core\Security\TrustedCallbackInterface.",
                    30
                ],
            ]
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/pre_render_callback_rule/src/RenderArrayWithPreRenderCallback.php',
            []
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/pre_render_callback_rule/src/FormWithClosure.php',
            [
                [
                    "#pre_render may not contain a closure at key '2' as forms may be serialized and serialization of closures is not allowed.",
                    35
                ]
            ]
        ];
    }


}
