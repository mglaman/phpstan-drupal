<?php

namespace Drupal\Tests\module_with_tests\Functional;

use Drupal\Tests\BrowserTestBase;

class DefaultThemeTest extends BrowserTestBase {

    protected $defaultTheme = 'stark';

    public function testFoo() {
        $this->drupalGet('/');
    }

}
