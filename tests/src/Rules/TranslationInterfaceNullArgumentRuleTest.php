<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\DataProvider;

final class TranslationInterfaceNullArgumentRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): Rule
    {
        /** @phpstan-ignore phpstanApi.classConstant */
        return self::getContainer()->getByType(CallMethodsRule::class);
    }

    /**
     * @dataProvider resultData
     *
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
                    "Parameter #2 \$args of method Drupal\\Core\\StringTranslation\\TranslationInterface::translate() expects array<string, bool|float|int|string|Stringable>, array<string, null> given.",
                    11,
                ],
                [
                    "Parameter #4 \$args of method Drupal\\Core\\StringTranslation\\TranslationInterface::formatPlural() expects array<string, bool|float|int|string|Stringable>, array<string, null> given.",
                    12,
                ],
                [
                    "Parameter #2 \$args of method Drupal\\Core\\StringTranslation\\TranslationInterface::translate() expects array<string, bool|float|int|string|Stringable>, array<string, string|null> given.",
                    16,
                ],
                [
                    "Parameter #4 \$args of method Drupal\\Core\\StringTranslation\\TranslationInterface::formatPlural() expects array<string, bool|float|int|string|Stringable>, array<string, string|null> given.",
                    17,
                ],
                [
                    "Parameter #2 \$args of method TranslationInterfaceNullArgTest\\UsesStringTranslationTrait::t() expects array<string, bool|float|int|string|Stringable>, array<string, null> given.",
                    49,
                ],
                [
                    "Parameter #4 \$args of method TranslationInterfaceNullArgTest\\UsesStringTranslationTrait::formatPlural() expects array<string, bool|float|int|string|Stringable>, array<string, string|null> given.",
                    50,
                ],
            ],
        ];
    }
}
