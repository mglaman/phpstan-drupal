<?php

namespace Drupal\phpstan_fixtures;

use Drupal\Core\Cache\CacheableMetadata;

class UsesIncorrectCacheableDependency {
    public function test() {
        $element = [];
        $cacheable_metadata = CacheableMetadata::createFromRenderArray($element);

        $object = new \Stdclass;
        $cacheable_metadata->addCacheableDependency($object);
    }
}
