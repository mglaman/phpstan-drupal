<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\ModuleLoadInclude;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class ModuleLoadIncludeRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return self::getContainer()->getByType(ModuleLoadInclude::class);
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__ . '/../../fixtures/drupal/modules/module_load_include_fixture/module_load_include_fixture.module'
        ],
        [
            [
                'File core/modules/locale/locale.translationzzzz.inc could not be loaded from module_load_include.',
                10
            ]
        ]);
    }

}
