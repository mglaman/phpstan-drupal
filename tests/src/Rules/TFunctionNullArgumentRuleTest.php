<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Functions\CallToFunctionParametersRule;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\DataProvider;

final class TFunctionNullArgumentRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): Rule
    {
        /** @phpstan-ignore phpstanApi.constructor */
        return new CallToFunctionParametersRule(
            $this->createReflectionProvider(),
            /** @phpstan-ignore phpstanApi.classConstant */
            self::getContainer()->getByType(FunctionCallParametersCheck::class),
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
            __DIR__ . '/data/t-function-null-argument.php',
            [
                [
                    "Parameter #2 \$args of function t expects array<string, bool|float|int|string|Stringable>, array<string, null> given.",
                    8,
                ],
                [
                    "Parameter #2 \$args of function t expects array<string, bool|float|int|string|Stringable>, array<string, string|null> given.",
                    9,
                ],
            ],
        ];
    }
}
