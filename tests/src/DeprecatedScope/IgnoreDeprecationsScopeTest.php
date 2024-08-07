<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\DeprecatedScope;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Deprecations\CallToDeprecatedFunctionRule;
use PHPStan\Rules\Deprecations\DeprecatedScopeHelper;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

final class IgnoreDeprecationsScopeTest extends DrupalRuleTestCase {

    protected function getRule(): Rule
    {
        // @phpstan-ignore-next-line
        return new CallToDeprecatedFunctionRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(DeprecatedScopeHelper::class)
        );
    }

    public function testCustomScope(): void
    {
        if (!class_exists(IgnoreDeprecations::class)) {
            $errors = [
                [
                    'Call to deprecated function Deprecated\deprecated_function().',
                    12,
                ],
                [
                    'Call to deprecated function Deprecated\deprecated_function().',
                    20,
                ],
                [
                    'Call to deprecated function Deprecated\deprecated_function().',
                    25,
                ],
            ];
        } else {
            $errors = [
                [
                    'Call to deprecated function Deprecated\deprecated_function().',
                    20,
                ],
            ];
        }
        require_once __DIR__ . '/data/deprecated-data-definition.php';
        $this->analyse(
            [__DIR__ . '/data/ignore-deprecations.php'],
            $errors
        );
    }
}
