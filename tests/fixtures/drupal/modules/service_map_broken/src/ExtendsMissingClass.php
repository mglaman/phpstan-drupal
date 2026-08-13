<?php

declare(strict_types=1);

namespace Drupal\service_map_broken;

use Drupal\missing_module\RenderConverter;

// The parent class belongs to a module that is not available. Declaring this
// class throws an Error, which the service map must survive.
final class ExtendsMissingClass extends RenderConverter
{

}
