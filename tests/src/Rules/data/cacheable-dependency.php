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

class UsesCorrectCacheableDependency {
    public function test() {
        $element = [];
        $cacheable_metadata = CacheableMetadata::createFromRenderArray($element);

        // This should NOT trigger an error - CacheableMetadata implements CacheableDependencyInterface
        $correct_dependency = new CacheableMetadata();
        $cacheable_metadata->addCacheableDependency($correct_dependency);
    }
}

class MultipleCacheableDependencyCalls {
    public function test() {
        $element = [];
        $cacheable_metadata = CacheableMetadata::createFromRenderArray($element);

        // Correct usage
        $correct_dependency = new CacheableMetadata();
        $cacheable_metadata->addCacheableDependency($correct_dependency);

        // Incorrect usage - should trigger error
        $object = new \StdClass;
        $cacheable_metadata->addCacheableDependency($object);

        // Another incorrect usage - should trigger error
        $another_object = new \DateTime();
        $cacheable_metadata->addCacheableDependency($another_object);
    }
}

