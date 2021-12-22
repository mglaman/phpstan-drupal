<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use mglaman\PHPStanDrupal\Tests\Rules\DummyRule;

class LinkItemTestBug3254966Test extends DrupalRuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new DummyRule();
    }

    public function testCrash(): void
    {
        $this->analyse(
            [
                __DIR__ . '/../fixtures/drupal/core/modules/link/tests/src/Kernel/LinkItemTest.php'
            ],
            []
        );
    }

}
