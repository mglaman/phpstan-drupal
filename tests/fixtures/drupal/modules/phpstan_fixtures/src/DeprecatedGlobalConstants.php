<?php

namespace Drupal\phpstan_fixtures;

class DeprecatedGlobalConstants {
    public function test() {
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone(DATETIME_STORAGE_TIMEZONE));
        $date->format(DATETIME_DATE_STORAGE_FORMAT);
    }
}
