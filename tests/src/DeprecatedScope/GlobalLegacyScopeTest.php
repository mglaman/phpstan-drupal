<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\DeprecatedScope;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Deprecations\CallToDeprecatedFunctionRule;
use PHPStan\Rules\Deprecations\DeprecatedScopeHelper;
use PHPStan\Rules\Rule;

final class GlobalLegacyScopeTest extends DrupalRuleTestCase {

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
        require_once __DIR__ . '/data/group-legacy-data-definition.php';
        $this->analyse(
            [__DIR__ . '/data/group-legacy.php'],
            [
                [
                    'Call to deprecated function GroupLegacy\deprecated_function().',
                    19,
                ],
            ]
        );
    }
}
