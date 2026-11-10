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

        $correct_dependency = new CacheableMetadata();
        $cacheable_metadata->addCacheableDependency($correct_dependency);
    }
}

class MultipleCacheableDependencyCalls {
    public function test() {
        $element = [];
        $cacheable_metadata = CacheableMetadata::createFromRenderArray($element);

        $correct_dependency = new CacheableMetadata();
        $cacheable_metadata->addCacheableDependency($correct_dependency);

        $object = new \StdClass;
        $cacheable_metadata->addCacheableDependency($object);

        $another_object = new \DateTime();
        $cacheable_metadata->addCacheableDependency($another_object);
    }
}

class RendererInterfaceTestCase {
    public function testCorrectUsage(\Drupal\Core\Render\RendererInterface $renderer) {
        $elements = [];

        $correct_dependency = new CacheableMetadata();
        $renderer->addCacheableDependency($elements, $correct_dependency);
    }

    public function testIncorrectUsage(\Drupal\Core\Render\RendererInterface $renderer) {
        $elements = [];

        $object = new \StdClass;
        $renderer->addCacheableDependency($elements, $object);
    }
}


