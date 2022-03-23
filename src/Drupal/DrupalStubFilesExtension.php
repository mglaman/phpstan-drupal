<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\PhpDoc\StubFilesExtension;
use Symfony\Component\Finder\Finder;

final class DrupalStubFilesExtension implements StubFilesExtension
{
    public function getFiles(): array
    {
        $files = [];
        $finder = Finder::create()->files()->name('*.stub')->in(__DIR__ . '/../../stubs');
        foreach ($finder as $file) {
            $files[] = $file->getPathname();
        }
        return $files;
    }
}
