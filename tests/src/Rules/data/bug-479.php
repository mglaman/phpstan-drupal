<?php

namespace Bug479Example;

class TestClass {

    /**
     * Tests non-chained entity queries with access check.
     */
    public function bug479(string $entity_type): void
    {
        $query = \Drupal::entityQuery('entity_form_display');
        $query->execute();
    }
}
