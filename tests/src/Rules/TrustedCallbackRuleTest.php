<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;


use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use mglaman\PHPStanDrupal\Rules\Drupal\TrustedCallbackRule;
use PHPStan\Rules\Rule;

final class TrustedCallbackRuleTest extends DrupalRuleTestCase {

    protected function getRule(): Rule
    {
        return new TrustedCallbackRule(
            $this->createReflectionProvider(),
            self::getContainer()->getByType(ServiceMap::class)
        );
    }

    /**
     * @dataProvider fileData
     */
    public function testRule(string $path, array $errorMessages): void
    {
        $this->analyse([$path], $errorMessages);
    }

    public static function fileData(): \Generator
    {
        $pre_render_callback_rule_set_one = [
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
                "#pre_render callback 'sample_pre_render…' at key '1' is not trusted.",
                27,
                'Change record: https://www.drupal.org/node/2966725.',
            ],
        ];
        if (version_compare(\Drupal::VERSION, '10.1', '<')) {
            $pre_render_callback_rule_set_one[] = [
                "#pre_render callback class 'static(Drupal\pre_render_callback_rule\NotTrustedCallback)' at key '3' does not implement Drupal\Core\Security\TrustedCallbackInterface.",
                29,
                'Change record: https://www.drupal.org/node/2966725.',
            ];
            $pre_render_callback_rule_set_one[] = [
                "#pre_render callback class 'Drupal\pre_render_callback_rule\NotTrustedCallback' at key '4' does not implement Drupal\Core\Security\TrustedCallbackInterface.",
                30,
                'Change record: https://www.drupal.org/node/2966725.',
            ];
        } else {
            $pre_render_callback_rule_set_one[] = [
                "#pre_render callback method 'array{static(Drupal\pre_render_callback_rule\NotTrustedCallback), 'unsafeCallback'}' at key '3' does not implement attribute \Drupal\Core\Security\Attribute\TrustedCallback.",
                29,
                'Change record: https://www.drupal.org/node/3349470',
            ];
            $pre_render_callback_rule_set_one[] = [
                "#pre_render callback method 'array{'\\\Drupal\\\pre_render…', 'unsafeCallback'}' at key '4' does not implement attribute \Drupal\Core\Security\Attribute\TrustedCallback.",
                30,
                'Change record: https://www.drupal.org/node/3349470',
            ];
        }
        yield [
          __DIR__ . '/../../fixtures/drupal/modules/pre_render_callback_rule/pre_render_callback_rule.module',
            $pre_render_callback_rule_set_one
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/pre_render_callback_rule/src/RenderArrayWithPreRenderCallback.php',
            []
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/pre_render_callback_rule/src/RenderCallbackInterfaceObject.php',
            []
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/pre_render_callback_rule/src/LazyBuilderWithConstant.php',
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
        yield [
            __DIR__ . '/../../fixtures/drupal/core/lib/Drupal/Core/Access/RouteProcessorCsrf.php',
            []
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/core/lib/Drupal/Core/Render/PlaceholderGenerator.php',
            []
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/core/lib/Drupal/Core/Render/Renderer.php',
            []
        ];

        if (version_compare(\Drupal::VERSION, '10.1', '<')) {
            $bug424 = [
                [
                    "#lazy_builder callback class 'static(Bug424\Foo)' at key '0' does not implement Drupal\Core\Security\TrustedCallbackInterface.",
                    10,
                    "Change record: https://www.drupal.org/node/2966725."
                ],
                [
                    "#lazy_builder callback class 'static(Bug424\Foo)' at key '0' does not implement Drupal\Core\Security\TrustedCallbackInterface.",
                    17,
                    "Change record: https://www.drupal.org/node/2966725."
                ]
            ];
        } else {
            $bug424 = [
                [
                    "#lazy_builder callback method 'array{static(Bug424\Foo), 'contentLazyBuilder'}' at key '0' does not implement attribute \Drupal\Core\Security\Attribute\TrustedCallback.",
                    10,
                    "Change record: https://www.drupal.org/node/3349470"
                ],
                [
                    "#lazy_builder callback method 'array{class-string<static(Bug424\Foo)>, 'contentLazyBuilder'}' at key '0' does not implement attribute \Drupal\Core\Security\Attribute\TrustedCallback.",
                    17,
                    "Change record: https://www.drupal.org/node/3349470"
                ]
            ];
        }
        yield [
            __DIR__ . '/data/bug-424.php',
            $bug424
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/core/modules/filter/src/FilterProcessResult.php',
            []
        ];
        if (version_compare(\Drupal::VERSION, '10.1', '>=')) {
            yield [
                __DIR__ . '/data/bug-527.php',
                [
                    [
                        "#lazy_builder callback method 'array{'Bug527\\\Foo', 'someCallback'}' at key '0' does not implement attribute \Drupal\Core\Security\Attribute\TrustedCallback.",
                        27,
                        "Change record: https://www.drupal.org/node/3349470"
                    ]
                ],
            ];
        }
        yield [
            __DIR__ . '/data/bug-543.php',
            []
        ];
        yield [
            __DIR__ . '/data/bug-554.php',
            [
                [
                    '#date_date_callbacks callback array{$this(Bug554\\TestClass), \'notExisting\'} at key \'2\' is not callable.',
                     76
                ],
                [
                    '#date_time_callbacks callback array{$this(Bug554\\TestClass), \'notExisting\'} at key \'2\' is not callable.',
                     77
                ],
            ]
        ];
    }


}
