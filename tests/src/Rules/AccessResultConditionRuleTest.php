<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\AccessResultConditionRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class AccessResultConditionRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new AccessResultConditionRule(true);
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__.'/data/access-result-condition-check.php'],
            [
                [
                    'Strict comparison using === between false and false will always evaluate to true.',
                    14
                ],
                [
                    'Strict comparison using !== between false and false will always evaluate to false.',
                    18
                ],
            ]
        );
    }
}
