<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Methods\MissingMethodParameterTypehintRule;
use PHPStan\Rules\Rule;

/**
 * Methods that inherit their PHPDoc from a stubbed Drupal class or interface
 * are not reported for arrays without a value type.
 *
 * @extends DrupalRuleTestCase<MissingMethodParameterTypehintRule>
 */
final class InheritedArrayParameterTypesTest extends DrupalRuleTestCase
{
    protected function getRule(): Rule
    {
        /** @phpstan-ignore phpstanApi.constructor, phpstanApi.classConstant */
        return self::getContainer()->getByType(MissingMethodParameterTypehintRule::class);
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/inherited-array-value-types.php'],
            []
        );
    }
}
