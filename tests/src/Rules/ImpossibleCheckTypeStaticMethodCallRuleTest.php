<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\Tests\TestClassSuffixNameRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeStaticMethodCallRule;
use PHPStan\Rules\Rule;

class ImpossibleCheckTypeStaticMethodCallRuleTest extends DrupalRuleTestCase
{

    private bool $treatPhpDocTypesAsCertain = false;

    private bool $reportAlwaysTrueInLastCondition = false;

    protected function getRule(): Rule
    {
        /** @phpstan-ignore phpstanApi.constructor */
        return new ImpossibleCheckTypeStaticMethodCallRule(
            /** @phpstan-ignore phpstanApi.constructor */
            new ImpossibleCheckTypeHelper(
                $this->createReflectionProvider(),
                $this->getTypeSpecifier(),
                [],
                $this->treatPhpDocTypesAsCertain,
            ),
            $this->treatPhpDocTypesAsCertain,
            $this->reportAlwaysTrueInLastCondition,
            true,
        );
    }

    protected function shouldTreatPhpDocTypesAsCertain(): bool
    {
        return $this->treatPhpDocTypesAsCertain;
    }


    public function testBug857(): void
    {
        $this->treatPhpDocTypesAsCertain = true;
        // It shouldn't report anything.
        $this->analyse([__DIR__ . '/data/bug-857.php'], []);
    }

}
