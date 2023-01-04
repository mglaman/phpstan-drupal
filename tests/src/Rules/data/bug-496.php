<?php

namespace Bug496Example;

class TestClass {

    /**
     * Tests non-chained entity queries with access check.
     */
    public function bug496(string $entity_type): void
    {
        $query = \Drupal::entityQuery('node');
        $query->accessCheck(FALSE);
        $query->condition('field_test', 'foo', '=');
        $query->execute();

        $query = \Drupal::entityQuery('node');
        $query->condition('field_test', 'foo', '=');
        $query->accessCheck(FALSE);
        $query->execute();
    }
}
