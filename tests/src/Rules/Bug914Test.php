<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Rule;

/**
 * Tests that ComputedItemListTrait::offsetExists does not produce false positives.
 *
 * @see https://github.com/mglaman/phpstan-drupal/pull/914
 */
final class Bug914Test extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        /** @phpstan-ignore phpstanApi.constructor, phpstanApi.classConstant */
        return self::getContainer()->getByType(CallMethodsRule::class);
    }

    public function testComputedItemListTraitOffsetExists(): void
    {
        $this->analyse(
            [__DIR__ . '/data/bug-914.php'],
            []
        );
    }
}
