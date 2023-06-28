<?php

namespace NightwatchSetup;

use Drupal\TestSite\TestSetupInterface;

final class TestSetup implements TestSetupInterface {

    public function setup()
    {
        \Drupal::service('module_installer')->install(['node', 'block']);
    }
}
