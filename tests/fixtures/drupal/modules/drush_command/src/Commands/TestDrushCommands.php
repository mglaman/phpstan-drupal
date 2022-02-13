<?php

namespace Drupal\drush_command\Commands;

use Drush\Commands\DrushCommands;

class TestDrushCommands extends DrushCommands {

    /**
     * Test
     *
     *  @command phpstan:example
     */
    public function batchProcess() {
        drush_backend_batch_process();
    }

}
