<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\NoRedundantTraitUseRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Reflection\ReflectionProvider;

final class NoRedundantTraitUseRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new NoRedundantTraitUseRule($this->createReflectionProvider());
    }

    /**
     * @dataProvider resultData
     *
     * @param list<array{0: string, 1: int, 2?: string|null}> $errorMessages
     */
    public function testRule(string $path, array $errorMessages): void
    {
        $this->analyse([$path], $errorMessages);
    }

    public static function resultData(): \Generator
    {
        yield [
            __DIR__ . '/data/no-redundant-trait-use.php',
            [
                [
                    'Class uses trait "BarTrait" redundantly as it is already included via trait "FooTrait".',
                    20
                ],
            ]
        ];

        yield [
            __DIR__ . '/data/no-redundant-trait-use-valid.php',
            []
        ];

        yield [
            __DIR__ . '/data/no-redundant-trait-use-single-trait.php',
            []
        ];

        yield [
            __DIR__ . '/data/no-redundant-trait-use-transitive.php',
            [
                [
                    'Class uses trait "BaseTrait" redundantly as it is already included via trait "WrapperTrait".',
                    20
                ],
            ]
        ];
    }
}