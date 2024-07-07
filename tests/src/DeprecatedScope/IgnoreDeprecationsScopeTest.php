<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\DeprecatedScope;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Deprecations\CallToDeprecatedFunctionRule;
use PHPStan\Rules\Deprecations\DeprecatedScopeHelper;
use PHPStan\Rules\Rule;

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
        require_once __DIR__ . '/data/deprecated-data-definition.php';
        $this->analyse(
            [__DIR__ . '/data/ignore-deprecations.php'],
            [
                [
                    'Call to deprecated function Deprecated\deprecated_function().',
                    20,
                ],
            ]
        );
    }
}
