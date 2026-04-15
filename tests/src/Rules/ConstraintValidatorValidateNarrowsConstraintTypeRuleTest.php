<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\ConstraintValidatorValidateNarrowsConstraintTypeRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class ConstraintValidatorValidateNarrowsConstraintTypeRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(ConstraintValidatorValidateNarrowsConstraintTypeRule::class);
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/constraint-validator.php'],
            [
                [
                    'Method UniqueItemValidator::validate() does not narrow the $constraint parameter type. Add assert($constraint instanceof UniqueItem) to allow PHPStan to infer constraint-specific properties.',
                    22,
                    'See https://www.drupal.org/project/drupal/issues/3246287',
                ],
            ]
        );
    }

}
