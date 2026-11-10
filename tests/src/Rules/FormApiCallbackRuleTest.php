<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use Drupal;
use mglaman\PHPStanDrupal\Rules\Drupal\FormApiCallback\FormApiCallbackAssignRule;
use mglaman\PHPStanDrupal\Rules\Drupal\FormApiCallback\FormApiCallbackChecker;
use mglaman\PHPStanDrupal\Rules\Drupal\FormApiCallback\FormApiCallbackRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;
use function version_compare;

/**
 * Tests both rules on one fixture: FormApiCallbackRule reports array literals,
 * FormApiCallbackAssignRule reports assignments.
 *
 * @extends DrupalRuleTestCase<FormApiCallbackRule|FormApiCallbackAssignRule>
 */
final class FormApiCallbackRuleTest extends DrupalRuleTestCase
{

    private const FIXTURE = __DIR__ . '/data/form-api-callbacks.php';

    private const NON_PUBLIC_TIP = 'Make the method public. Drupal calls form callbacks from outside the class.';

    private const SERVICE_NOTATION_TIP = "Use a static method, or '::method' in a form class.";

    private bool $testsAssignments = false;

    protected function getRule(): Rule
    {
        $checker = self::getContainer()->getByType(FormApiCallbackChecker::class);
        return $this->testsAssignments ? new FormApiCallbackAssignRule($checker) : new FormApiCallbackRule($checker);
    }

    public function testArrayLiterals(): void
    {
        $resolvesCallableDefinitions = version_compare(Drupal::VERSION, '11.3', '>=');

        $errors = [
            [
                "#submit callback '::missingMethod' refers to FormApiCallbacks\\ExampleForm::missingMethod(), which does not exist.",
                52,
            ],
            [
                "#submit callback '::privateHandler' refers to FormApiCallbacks\\ExampleForm::privateHandler(), which is not public.",
                53,
                self::NON_PUBLIC_TIP,
            ],
            [
                "#submit callback 'missing_function' refers to function missing_function(), which does not exist.",
                55,
            ],
            [
                "#submit callback [\$this, 'missingMethod'] refers to FormApiCallbacks\\ExampleForm::missingMethod(), which does not exist.",
                57,
            ],
            [
                "#submit callback [static::class, 'ajaxCallback'] calls non-static method FormApiCallbacks\\ExampleForm::ajaxCallback() statically.",
                58,
                "Use '::ajaxCallback' to call it on the form object, or make the method static."
                    . ($resolvesCallableDefinitions ? " The string 'FormApiCallbacks\\ExampleForm::ajaxCallback' also works: Drupal creates the object." : ''),
            ],
            [
                "#submit callback [\\FormApiCallbacks\\Callbacks::class, 'instanceCallback'] calls non-static method FormApiCallbacks\\Callbacks::instanceCallback() statically.",
                60,
                'Make the method static.'
                    . ($resolvesCallableDefinitions ? " The string 'FormApiCallbacks\\Callbacks::instanceCallback' also works: Drupal creates the object." : ''),
            ],
            [
                "#submit callback [\\FormApiCallbacks\\Callbacks::class, 'missing'] refers to FormApiCallbacks\\Callbacks::missing(), which does not exist.",
                61,
            ],
            [
                "#submit callback [\\FormApiCallbacks\\Callbacks::class, 'privateStatic'] refers to FormApiCallbacks\\Callbacks::privateStatic(), which is not public.",
                62,
                self::NON_PUBLIC_TIP,
            ],
        ];
        if (!$resolvesCallableDefinitions) {
            $errors[] = [
                "#submit callback 'FormApiCallbacks\\Callbacks::instanceCallback' calls non-static method FormApiCallbacks\\Callbacks::instanceCallback() statically.",
                64,
                "Make the method static. Drupal 11.3 and later accept 'FormApiCallbacks\\Callbacks::instanceCallback' for non-static methods.",
            ];
        }
        $errors[] = [
            "#submit callback 'FormApiCallbacks\\Callbacks::missing' refers to FormApiCallbacks\\Callbacks::missing(), which does not exist.",
            65,
        ];
        if ($resolvesCallableDefinitions) {
            $errors[] = [
                "#submit callback 'messenger:missingMethod' refers to Drupal\\Core\\Messenger\\Messenger::missingMethod(), which does not exist.",
                68,
            ];
        } else {
            $errors[] = [
                "#submit callback static::class . '::ajaxCallback' calls non-static method FormApiCallbacks\\ExampleForm::ajaxCallback() statically.",
                66,
                "Use '::ajaxCallback' to call it on the form object, or make the method static. Drupal 11.3 and later accept 'FormApiCallbacks\\ExampleForm::ajaxCallback' for non-static methods.",
            ];
            foreach ([67 => 'messenger:addStatus', 68 => 'messenger:missingMethod', 69 => 'unknown_service:method'] as $line => $callback) {
                $errors[] = [
                    "#submit callback '$callback' uses the service:method notation, which Drupal supports for form callbacks in 11.3 and later.",
                    $line,
                    self::SERVICE_NOTATION_TIP,
                ];
            }
        }
        $errors[] = [
            "#ajax callback '::missingAjax' refers to FormApiCallbacks\\ExampleForm::missingAjax(), which does not exist.",
            76,
        ];
        $errors[] = [
            "#after_build callback [\$this, 'missingMethod'] refers to FormApiCallbacks\\NotAForm::missingMethod(), which does not exist.",
            133,
        ];

        $this->analyse([self::FIXTURE], $errors);
    }

    public function testAssignments(): void
    {
        $this->testsAssignments = true;
        $resolvesCallableDefinitions = version_compare(Drupal::VERSION, '11.3', '>=');

        $classColonMethodErrors = $resolvesCallableDefinitions
            ? [
                [
                    "#submit callback \\FormApiCallbacks\\Callbacks::class . ':missing' refers to FormApiCallbacks\\Callbacks::missing(), which does not exist.",
                    145,
                ],
            ]
            : [
                [
                    "#submit callback \\FormApiCallbacks\\Callbacks::class . ':instanceCallback' uses the service:method notation, which Drupal supports for form callbacks in 11.3 and later.",
                    144,
                    self::SERVICE_NOTATION_TIP,
                ],
                [
                    "#submit callback \\FormApiCallbacks\\Callbacks::class . ':missing' uses the service:method notation, which Drupal supports for form callbacks in 11.3 and later.",
                    145,
                    self::SERVICE_NOTATION_TIP,
                ],
            ];

        $this->analyse([self::FIXTURE], [
            [
                "#validate callback '::missingValidate' refers to FormApiCallbacks\\ExampleForm::missingValidate(), which does not exist.",
                80,
            ],
            [
                "#validate callback 'another_missing_function' refers to function another_missing_function(), which does not exist.",
                81,
            ],
            [
                "#ajax callback [static::class, 'ajaxCallback'] calls non-static method FormApiCallbacks\\ExampleForm::ajaxCallback() statically.",
                82,
                "Use '::ajaxCallback' to call it on the form object, or make the method static."
                    . ($resolvesCallableDefinitions ? " The string 'FormApiCallbacks\\ExampleForm::ajaxCallback' also works: Drupal creates the object." : ''),
            ],
            [
                "#process callback '::missingProcess' refers to FormApiCallbacks\\ExampleForm::missingProcess(), which does not exist.",
                84,
            ],
            [
                "#element_validate callback '::missingElementValidate' refers to FormApiCallbacks\\ExampleForm::missingElementValidate(), which does not exist.",
                86,
            ],
            [
                "#submit callback [static::class, 'ajaxCallback'] calls non-static method FormApiCallbacks\\ExampleForm::ajaxCallback() statically.",
                107,
                'Make the method static.'
                    . ($resolvesCallableDefinitions ? " The string 'FormApiCallbacks\\ExampleForm::ajaxCallback' also works: Drupal creates the object." : ''),
            ],
            [
                "#submit callback [static::class, 'submitForm'] calls non-static method FormApiCallbacks\\AbstractForm::submitForm() statically.",
                118,
                'Make the method static.'
                    . ($resolvesCallableDefinitions ? " The string 'FormApiCallbacks\\AbstractForm::submitForm' also works: Drupal creates the object." : ''),
            ],
            [
                "#validate callback 'form_api_callbacks_missing_validate' refers to function form_api_callbacks_missing_validate(), which does not exist.",
                141,
            ],
            [
                "#ajax callback [\\FormApiCallbacks\\Callbacks::class, 'instanceCallback'] calls non-static method FormApiCallbacks\\Callbacks::instanceCallback() statically.",
                143,
                'Make the method static.'
                    . ($resolvesCallableDefinitions ? " The string 'FormApiCallbacks\\Callbacks::instanceCallback' also works: Drupal creates the object." : ''),
            ],
            ...$classColonMethodErrors,
        ]);
    }
}
