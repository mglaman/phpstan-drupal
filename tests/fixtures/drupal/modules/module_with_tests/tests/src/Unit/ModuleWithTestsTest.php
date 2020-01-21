<?php

namespace Drupal\Tests\module_with_tests\Unit;

use Drupal\Tests\module_with_tests\Traits\ModuleWithTestsTrait;
use Drupal\Tests\UnitTestCase;

/**
 * This is a dummy test class.
 *
 * @group module_with_tests
 */
class ModuleWithTestsTest extends UnitTestCase {

  use ModuleWithTestsTrait;

  /**
   * A dummy test.
   */
  public function testModule() {
    $this->assertTrue($this->dummyTrait());
  }

}
