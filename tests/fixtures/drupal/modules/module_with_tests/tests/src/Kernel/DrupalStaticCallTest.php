<?php

namespace Drupal\Tests\module_with_tests\Kernel;

use Drupal;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\module_with_tests\Traits\ModuleWithTestsTrait;

/**
 * This is a dummy test class.
 *
 * @group module_with_tests
 */
class DrupalStaticCallTest extends KernelTestBase {

    use ModuleWithTestsTrait;

    /**
     * A dummy test.
     */
    public function testModule() {
        $request = Drupal::hasRequest();
        $this->assertFalse($request);
    }

}
