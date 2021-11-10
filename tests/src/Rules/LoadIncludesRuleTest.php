<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\LoadIncludes;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class LoadIncludesRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        $params = self::getContainer()->getParameter('drupal');
        return new LoadIncludes($params['drupal_root']);
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/phpstan_fixtures.module'
        ],
            [
                [
                    'File tests/fixtures/drupal/modules/phpstan_fixtures/phpstan_fixtures.fetch.inc could not be loaded from Drupal\Core\Extension\ModuleHandlerInterface::loadInclude',
                    30
                ]
            ]);
    }

}
