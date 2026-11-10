<?php

namespace RenderCallbackKeyFilter;

final class PlaceholderCache
{
    public function cacheableKeys(array $element): array
    {
        return array_intersect_key($element, ['#lazy_builder' => TRUE, '#cache' => TRUE]);
    }
}
