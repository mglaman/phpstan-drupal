<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Deprecations\ConstraintNamedArgumentsRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class ConstraintNamedArgumentsRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new ConstraintNamedArgumentsRule();
    }

    public function testRule(): void
    {
        $tip = 'See https://www.drupal.org/node/3554746';
        $missingConstructor = 'Constraint plugin %s has options, but no constructor with the #[HasNamedArguments] attribute. Drupal 12 passes the options as named arguments, so the constraint fails when it gets options. Add a constructor with a parameter for each option and the #[HasNamedArguments] attribute.';
        $errors = [
            [sprintf($missingConstructor, 'ConstraintNamedArguments\NoConstructorConstraint'), 11, $tip],
            [sprintf($missingConstructor, 'ConstraintNamedArguments\AnnotatedNoConstructorConstraint'), 23, $tip],
            [sprintf($missingConstructor, 'ConstraintNamedArguments\ArrayConstructorConstraint'), 27, $tip],
            ['The constructor of constraint plugin ConstraintNamedArguments\MissingParameterConstraint has no parameter for these options: message. Drupal passes the options as named arguments, so the constraint fails when it gets one of them.', 36, $tip],
        ];
        // Drupal 11.4 deprecated the options array.
        if (version_compare(\Drupal::VERSION, '11.4', '<')) {
            $errors = [];
        }
        $this->analyse([__DIR__ . '/data/constraint-named-arguments.php'], $errors);
    }
}
