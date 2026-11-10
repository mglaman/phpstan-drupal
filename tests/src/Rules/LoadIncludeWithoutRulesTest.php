<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Functions\CallToNonExistentFunctionRule;
use PHPStan\Rules\Rule;

/**
 * Functions from loadInclude() files are known without the include rules.
 *
 * The LoadIncludes rule doesn't run here, so only the return type extension
 * can load the include.
 *
 * @see https://github.com/mglaman/phpstan-drupal/issues/741
 */
final class LoadIncludeWithoutRulesTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        /** @phpstan-ignore phpstanApi.classConstant */
        return self::getContainer()->getByType(CallToNonExistentFunctionRule::class);
    }

    public function testFunctionFromIncludeIsKnown(): void
    {
        $this->analyse([__DIR__ . '/data/load-include-without-rules.php'], []);
    }
}
