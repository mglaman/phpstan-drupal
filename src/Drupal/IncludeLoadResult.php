<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

enum IncludeLoadResult
{
    case Loaded;
    case ModuleNotFound;
    case FileNotFound;
    case NotLoadable;
}
