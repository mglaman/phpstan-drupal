<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\AccessResultBooleanContextRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class AccessResultBooleanContextRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new AccessResultBooleanContextRule(true);
    }

    public function testRule(): void
    {
        $message = 'Access result used in a boolean context. An access result object is always truthy; use ->isAllowed(), ->isForbidden(), or ->isNeutral() to inspect it.';
        $this->analyse(
            [__DIR__.'/data/access-result-boolean-context.php'],
            [
                [$message, 17],
                [$message, 19],
                [$message, 21],
                [$message, 22],
                [$message, 22],
                [$message, 23],
                [$message, 23],
                [$message, 24],
                [$message, 25],
                [$message, 28],
            ]
        );
    }
}
