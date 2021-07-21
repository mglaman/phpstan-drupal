<?php

namespace Drupal\Tests\module_with_tests\Functional;

use Drupal\Tests\BrowserTestBase;

class MissingDefaultThemeTest extends BrowserTestBase {

    public function testFoo() {
        $this->drupalGet('/');
    }

}
