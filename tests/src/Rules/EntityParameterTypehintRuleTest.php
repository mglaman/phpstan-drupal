<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Functions\MissingFunctionParameterTypehintRule;
use PHPStan\Rules\Rule;

final class EntityParameterTypehintRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): Rule
    {
        /** @phpstan-ignore phpstanApi.constructor, phpstanApi.classConstant */
        return self::getContainer()->getByType(MissingFunctionParameterTypehintRule::class);
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__.'/data/entity-parameters.php'],
            []
        );
    }
}
