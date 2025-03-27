<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeMethodCallRule;
use PHPStan\Rules\Rule;

final class AccessCheckImpossibleTypeCallRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        // @phpstan-ignore phpstanApi.constructor
        return new ImpossibleCheckTypeMethodCallRule(
        // @phpstan-ignore phpstanApi.constructor
            new ImpossibleCheckTypeHelper(
                $this->createReflectionProvider(),
                $this->getTypeSpecifier(),
                [],
                false,
                false
            ),
            true,
            false,
            false,
            false
        );
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/bug-496.php'],
            []
        );
    }
}
