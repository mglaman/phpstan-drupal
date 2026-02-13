<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Functions\MissingFunctionParameterTypehintRule;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;

final class EntityParameterTypehintRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): Rule
    {
        /** @phpstan-ignore phpstanApi.constructor */
        return new MissingFunctionParameterTypehintRule(
        /** @phpstan-ignore phpstanApi.constructor */
        new MissingTypehintCheck(
                true,
                [],
                true
            )
        );
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__.'/data/entity-parameters.php'],
            []
        );
    }
}
