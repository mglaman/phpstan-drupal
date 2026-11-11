<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Methods\MissingMethodReturnTypehintRule;
use PHPStan\Rules\Rule;

/**
 * Methods that inherit their PHPDoc from a stubbed Drupal class or interface
 * are not reported for arrays without a value type.
 *
 * @extends DrupalRuleTestCase<MissingMethodReturnTypehintRule>
 */
final class InheritedArrayReturnTypesTest extends DrupalRuleTestCase
{
    protected function getRule(): Rule
    {
        /** @phpstan-ignore phpstanApi.constructor, phpstanApi.classConstant */
        return self::getContainer()->getByType(MissingMethodReturnTypehintRule::class);
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/inherited-array-value-types.php'],
            []
        );
    }
}
