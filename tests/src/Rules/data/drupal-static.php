<?php

namespace DrupalStatic;

$class = new class () extends \Drupal\Core\StreamWrapper\LocalStream {

    public function getDirectoryPath()
    {
        return \Drupal::root();
    }

    public function getName()
    {
    }

    public function getDescription()
    {
    }

    public function getExternalUrl()
    {
    }
};
