<?php

namespace PHPStan\Drupal;

/**
 * Filters a RecursiveDirectoryIterator to discover extensions.
 *
 * Locally bundled version of \Drupal\Core\Extension\Discovery\RecursiveExtensionFilterIterator.
 *
 * @method bool isDir()
 */
class RecursiveExtensionFilterIterator extends \RecursiveFilterIterator
{

    /**
     * List of base extension type directory names to scan.
     *
     * Only these directory names are considered when starting a filesystem
     * recursion in a search path.
     *
     * @var array
     */
    protected $whitelist = [
        'profiles',
        'modules',
        'themes',
    ];

    /**
     * List of directory names to skip when recursing.
     *
     * These directories are globally ignored in the recursive filesystem scan;
     * i.e., extensions (of all types) are not able to use any of these names,
     * because their directory names will be skipped.
     *
     * @var array
     */
    protected $blacklist = [
        // Object-oriented code subdirectories.
        'src',
        'lib',
        'vendor',
        // Front-end.
        'assets',
        'css',
        'files',
        'images',
        'js',
        'misc',
        'templates',
        // Legacy subdirectories.
        'includes',
        // Test subdirectories.
        'fixtures',
        // @todo ./tests/Drupal should be ./tests/src/Drupal
        'Drupal',
    ];

    /**
     * Construct a RecursiveExtensionFilterIterator.
     *
     * @param \RecursiveIterator $iterator
     *   The iterator to filter.
     * @param array $blacklist
     *   (optional) Add to the blacklist of directories that should be filtered
     *   out during the iteration.
     */
    public function __construct(\RecursiveIterator $iterator, array $blacklist = [])
    {
        parent::__construct($iterator);
        $this->blacklist = array_merge($this->blacklist, $blacklist);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(): \RecursiveFilterIterator
    {
        $filter = parent::getChildren();
        if ($filter instanceof self) {
            // Pass on the blacklist.
            $filter->blacklist = $this->blacklist;
        }
        return $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function accept(): bool
    {
        $name = $this->current()->getFilename();
        // FilesystemIterator::SKIP_DOTS only skips '.' and '..', but not hidden
        // directories (like '.git').
        if ($name[0] === '.') {
            return false;
        }
        if ($this->isDir()) {
            // If this is a subdirectory of a base search path, only recurse into the
            // fixed list of expected extension type directory names. Required for
            // scanning the top-level/root directory; without this condition, we would
            // recurse into the whole filesystem tree that possibly contains other
            // files aside from Drupal.
            if ($this->current()->getSubPath() === '') {
                return in_array($name, $this->whitelist, true);
            }
            // 'config' directories are special-cased here, because every extension
            // contains one. However, those default configuration directories cannot
            // contain extensions. The directory name cannot be globally skipped,
            // because core happens to have a directory of an actual module that is
            // named 'config'. By explicitly testing for that case, we can skip all
            // other config directories, and at the same time, still allow the core
            // config module to be overridden/replaced in a profile/site directory
            // (whereas it must be located directly in a modules directory).
            if ($name === 'config') {
                return substr($this->current()->getPathname(), -14) === 'modules/config';
            }
            // Accept the directory unless the name is blacklisted.
            return !in_array($name, $this->blacklist, true);
        }

        // Only accept extension info files.
        return substr($name, -9) === '.info.yml';
    }
}
