<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use PHPStan\Rules\Functions\CallToNonExistentFunctionRule;

final class DrushIntegrationTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        // @phpstan-ignore-next-line
        return new CallToNonExistentFunctionRule(
            $this->createReflectionProvider(),
            true
        );
    }

    public function testPaths(): void
    {
        $this->analyse([
            __DIR__ . '/../fixtures/drupal/modules/drush_command/src/Commands/TestDrushCommands.php'
        ], []);
    }

}
