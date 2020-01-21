<?php

namespace Drupal\Tests\module_with_tests\TestSite;

use Drupal\TestSite\TestSetupInterface;

class ModuleWithTestsTestSite implements TestSetupInterface {

  protected $modules = [];

  public function setup() {
    $this->modules = ['module_with_tests', 'views'];
  }

}
