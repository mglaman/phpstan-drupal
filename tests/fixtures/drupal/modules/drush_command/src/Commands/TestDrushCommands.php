<?php

namespace Drupal\drush_command\Commands;

use Drush\Commands\DrushCommands;

class TestDrushCommands extends DrushCommands {

    /**
     * Test
     *
     *  @command phpstan:example
     */
    public function example() {
        if (drush_is_osx()) {
            $this->io()->writeln('macOS');
        } elseif (drush_is_cygwin() || drush_is_mingw()) {
            $this->io()->writeln('Windows');
        } else {
            $this->io()->writeln('Linux ¯\_(ツ)_/¯');
        }
    }

    public function batchProcess() {
        drush_backend_batch_process();
    }

}
