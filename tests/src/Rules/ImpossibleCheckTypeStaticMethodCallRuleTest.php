<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeStaticMethodCallRule;
use PHPStan\Rules\Rule;

class ImpossibleCheckTypeStaticMethodCallRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        /** @phpstan-ignore phpstanApi.constructor, phpstanApi.classConstant */
        return self::getContainer()->getByType(ImpossibleCheckTypeStaticMethodCallRule::class);
    }

    protected function shouldTreatPhpDocTypesAsCertain(): bool
    {
        return true;
    }


    public function testBug857(): void
    {
        // It shouldn't report anything.
        $this->analyse([__DIR__ . '/data/bug-857.php'], []);
    }

}
