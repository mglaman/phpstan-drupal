<?php

use Symfony\Component\Yaml\Yaml;

// Direct call — should be flagged.
$data = Yaml::parse('foo: bar');

// Fully-qualified call — should be flagged.
$data = \Symfony\Component\Yaml\Yaml::parse('foo: bar');

// Drupal's wrapper — should not be flagged.
$data = \Drupal\Component\Serialization\Yaml::decode('foo: bar');
