<?php

namespace Drupal\TestSite;

class ModuleWithTestsTestSite implements TestSetupInterface {

  public function setup() {
      \Drupal::service('module_installer')->install(['quicklink_test']);


  }

}
