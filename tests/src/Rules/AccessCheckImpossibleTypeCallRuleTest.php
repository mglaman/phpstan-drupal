<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeMethodCallRule;
use PHPStan\Rules\Rule;

final class AccessCheckImpossibleTypeCallRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        /** @phpstan-ignore phpstanApi.constructor, phpstanApi.classConstant */
        return self::getContainer()->getByType(ImpossibleCheckTypeMethodCallRule::class);
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/bug-496.php'],
            []
        );
    }
}
