<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Classes\ConsistentConstructorHelper;
use PHPStan\Rules\Classes\InstantiationRule;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPUnit\Framework\Attributes\DataProvider;

final class FormattableMarkupNullArgumentRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): Rule
    {
        $container = self::getContainer();
        /** @phpstan-ignore phpstanApi.constructor */
        return new InstantiationRule(
            $container,
            $this->createReflectionProvider(),
            /** @phpstan-ignore phpstanApi.classConstant */
            $container->getByType(FunctionCallParametersCheck::class),
            /** @phpstan-ignore phpstanApi.classConstant */
            $container->getByType(ClassNameCheck::class),
            /** @phpstan-ignore phpstanApi.classConstant */
            $container->getByType(RuleLevelHelper::class),
            /** @phpstan-ignore phpstanApi.classConstant */
            $container->getByType(ConsistentConstructorHelper::class),
            false,
            true,
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
            __DIR__ . '/data/formattable-markup-null-argument.php',
            [
                [
                    "Parameter #2 \$arguments of class Drupal\\Component\\Render\\FormattableMarkup constructor expects array<string, bool|float|int|string|Stringable>, array<string, null> given.",
                    12,
                ],
                [
                    "Parameter #2 \$arguments of class Drupal\\Core\\StringTranslation\\TranslatableMarkup constructor expects array<string, bool|float|int|string|Stringable>, array<string, null> given.",
                    15,
                ],
                [
                    "Parameter #4 \$args of class Drupal\\Core\\StringTranslation\\PluralTranslatableMarkup constructor expects array<string, bool|float|int|string|Stringable>, array<string, null> given.",
                    18,
                ],
                [
                    "Parameter #2 \$arguments of class Drupal\\Component\\Render\\FormattableMarkup constructor expects array<string, bool|float|int|string|Stringable>, array<string, string|null> given.",
                    21,
                ],
                [
                    "Parameter #2 \$arguments of class Drupal\\Core\\StringTranslation\\TranslatableMarkup constructor expects array<string, bool|float|int|string|Stringable>, array<string, string|null> given.",
                    22,
                ],
                [
                    "Parameter #4 \$args of class Drupal\\Core\\StringTranslation\\PluralTranslatableMarkup constructor expects array<string, bool|float|int|string|Stringable>, array<string, string|null> given.",
                    23,
                ],
                [
                    "Parameter #2 \$arguments of class Drupal\\Component\\Render\\FormattableMarkup constructor expects array<string, bool|float|int|string|Stringable>, array<string, string|null> given.",
                    44,
                ],
                [
                    "Parameter #2 \$arguments of class Drupal\\Core\\StringTranslation\\TranslatableMarkup constructor expects array<string, bool|float|int|string|Stringable>, array<string, string|null> given.",
                    45,
                ],
                [
                    "Parameter #4 \$args of class Drupal\\Core\\StringTranslation\\PluralTranslatableMarkup constructor expects array<string, bool|float|int|string|Stringable>, array<string, string|null> given.",
                    46,
                ],
                [
                    "Parameter #2 \$arguments of class Drupal\\Component\\Render\\FormattableMarkup constructor expects array<string, bool|float|int|string|Stringable>, array<string, array<int, string>> given.",
                    62,
                ],
            ],
        ];
    }
}
