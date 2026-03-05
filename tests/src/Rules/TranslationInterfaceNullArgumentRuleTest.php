<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Methods\MethodCallCheck;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\DataProvider;

final class TranslationInterfaceNullArgumentRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): Rule
    {
        $container = self::getContainer();
        /** @phpstan-ignore phpstanApi.constructor */
        return new CallMethodsRule(
            /** @phpstan-ignore phpstanApi.classConstant */
            $container->getByType(MethodCallCheck::class),
            /** @phpstan-ignore phpstanApi.classConstant */
            $container->getByType(FunctionCallParametersCheck::class),
        );
    }

    /**
     * @param list<array{0: string, 1: int, 2?: string|null}> $errorMessages
     */
    #[DataProvider('resultData')]
    public function testRule(string $path, array $errorMessages): void
    {
        $this->analyse([$path], $errorMessages);
    }

    public static function resultData(): \Generator
    {
        yield [
            __DIR__ . '/data/translation-interface-null-argument.php',
            [
                [
                    "Parameter #2 \$args of method Drupal\Core\StringTranslation\TranslationInterface::translate() expects array<non-empty-string, string|Stringable>, array{'@name': null} given.",
                    10,
                ],
                [
                    "Parameter #4 \$args of method Drupal\Core\StringTranslation\TranslationInterface::formatPlural() expects array<non-empty-string, string|Stringable>, array{'@name': null} given.",
                    11,
                ],
                [
                    "Parameter #2 \$args of method Drupal\Core\StringTranslation\TranslationInterface::translate() expects array<non-empty-string, string|Stringable>, array{'@name': string|null} given.",
                    15,
                ],
                [
                    "Parameter #4 \$args of method Drupal\Core\StringTranslation\TranslationInterface::formatPlural() expects array<non-empty-string, string|Stringable>, array{'@name': string|null} given.",
                    16,
                ],
                [
                    "Parameter #2 \$args of method Drupal\Core\StringTranslation\TranslationInterface::translate() expects array<non-empty-string, string|Stringable>, array{'': string} given.",
                    35,
                ],
                [
                    "Parameter #4 \$args of method Drupal\Core\StringTranslation\TranslationInterface::formatPlural() expects array<non-empty-string, string|Stringable>, array{'': string} given.",
                    36,
                ],
                [
                    "Parameter #2 \$args of method Drupal\Core\StringTranslation\TranslationInterface::translate() expects array<non-empty-string, string|Stringable>, non-empty-array<string, string> given.",
                    40,
                ],
                [
                    "Parameter #4 \$args of method Drupal\Core\StringTranslation\TranslationInterface::formatPlural() expects array<non-empty-string, string|Stringable>, non-empty-array<string, string> given.",
                    41,
                ],
            ],
        ];
    }
}
