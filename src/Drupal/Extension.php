<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use function explode;
use function file_get_contents;
use function is_array;
use function sprintf;
use function strpos;
use function trim;

/**
 * Defines an extension (file) object.
 *
 * Bundled version of \Drupal\Core\Extension\Extension.
 */
class Extension
{

    /**
     * The type of the extension (e.g., 'module').
     *
     * @var string
     */
    protected $type;

    /**
     * The relative pathname of the extension (e.g.,
     * 'core/modules/node/node.info.yml').
     *
     * @var string
     */
    protected $pathname;

    /**
     * The filename of the main extension file (e.g., 'node.module').
     *
     * @var string|null
     */
    protected $filename;

    /**
     * An SplFileInfo instance for the extension's info file.
     *
     * Note that SplFileInfo is a PHP resource and resources cannot be serialized.
     *
     * @var ?\SplFileInfo
     */
    protected $splFileInfo;

    /**
     * The app root.
     *
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    public $subpath = '';

    /**
     * @var string
     */
    public $origin = '';

    /**
     * @var array|null
     */
    private $info;

    /**
     * @var string[]|null
     */
    private $dependencies;

    /**
     * Constructs a new Extension object.
     *
     * @param string $root
     *   The app root.
     * @param string $type
     *   The type of the extension; e.g., 'module'.
     * @param string $pathname
     *   The relative path and filename of the extension's info file; e.g.,
     *   'core/modules/node/node.info.yml'.
     * @param string $filename
     *   (optional) The filename of the main extension file; e.g., 'node.module'.
     */
    public function __construct($root, $type, $pathname, $filename = null)
    {
        $this->root = $root;
        $this->type = $type;
        $this->pathname = $pathname;
        $this->filename = $filename;
    }

    /**
     * Returns the type of the extension.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the internal name of the extension.
     *
     * @return string
     */
    public function getName(): string
    {
        return basename($this->pathname, '.info.yml');
    }

    /**
     * Returns the relative path of the extension.
     *
     * @return string
     */
    public function getPath(): string
    {
        return dirname($this->pathname);
    }

    public function getAbsolutePath(): string
    {
        return $this->root . DIRECTORY_SEPARATOR . $this->getPath();
    }

    /**
     * Returns the relative path and filename of the extension's info file.
     *
     * @return string
     */
    public function getPathname(): string
    {
        return $this->pathname;
    }

    /**
     * Returns the filename of the extension's info file.
     *
     * @return string
     */
    public function getFilename(): string
    {
        return basename($this->pathname);
    }

    /**
     * Returns the relative path of the main extension file, if any.
     *
     * @return string|null
     */
    public function getExtensionPathname(): ?string
    {
        if ($this->filename !== null) {
            return $this->getPath() . '/' . $this->filename;
        }

        return null;
    }

    /**
     * Returns the name of the main extension file, if any.
     *
     * @return string|null
     */
    public function getExtensionFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * Loads the main extension file, if any.
     *
     * @return bool
     *   TRUE if this extension has a main extension file, FALSE otherwise.
     */
    public function load(): bool
    {
        if ($this->filename !== null) {
            include_once $this->root . '/' . $this->getPath() . '/' . $this->filename;
            return true;
        }
        return false;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        if (is_array($this->dependencies)) {
            return $this->dependencies;
        }

        $info = $this->parseInfo();
        $dependencies = $info['dependencies'] ?? [];

        if ($dependencies === []) {
            return $this->dependencies = $dependencies;
        }

        $this->dependencies = [];

        // @see \Drupal\Core\Extension\Dependency::createFromString().
        foreach ($dependencies as $dependency) {
            if (strpos($dependency, ':') !== false) {
                [, $dependency] = explode(':', $dependency);
            }

            $parts = explode('(', $dependency, 2);
            $this->dependencies[] = trim($parts[0]);
        }

        return $this->dependencies;
    }

    private function parseInfo(): array
    {
        if (is_array($this->info)) {
            return $this->info;
        }

        $infoContent = file_get_contents(sprintf('%s/%s', $this->root, $this->getPathname()));
        if (false === $infoContent) {
            throw new RuntimeException(sprintf('Cannot read "%s', $this->getPathname()));
        }

        return $this->info = Yaml::parse($infoContent);
    }
}
