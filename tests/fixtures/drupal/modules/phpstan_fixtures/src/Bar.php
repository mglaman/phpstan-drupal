<?php

declare(strict_types=1);

namespace Drupal\phpstan_fixtures;

final class Bar implements BarInterface
{

    public function __construct(Foo $inner)
    {
    }

}
