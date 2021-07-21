<?php

namespace Drupal\Tests\module_with_tests\Functional;

class ExtendsDefaultThemeTest extends DefaultThemeTest {

    public function testBar() {
        $this->drupalGet('/');
    }

}
