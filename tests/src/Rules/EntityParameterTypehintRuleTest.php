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
        // @phpstan-ignore-next-line
        return new MissingFunctionParameterTypehintRule(
            // @phpstan-ignore-next-line
            new MissingTypehintCheck(
                true,
                true,
                true,
                true,
                []
            ),
            true
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
