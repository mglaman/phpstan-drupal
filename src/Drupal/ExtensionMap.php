<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

final class ExtensionMap
{
    /** @var array<string, Extension>  */
    private static $modules = [];

    /** @var array<string, Extension>  */
    private static $themes = [];

    /** @var array<string, Extension>  */
    private static $profiles = [];

    /**
     * @return Extension[]
     */
    public function getModules(): array
    {
        return self::$modules;
    }

    public function getModule(string $name): ?Extension
    {
        return self::$modules[$name] ?? null;
    }

    /**
     * @return Extension[]
     */
    public function getThemes(): array
    {
        return self::$themes;
    }

    public function getTheme(string $name): ?Extension
    {
        return self::$themes[$name] ?? null;
    }

    /**
     * @return Extension[]
     */
    public function getProfiles(): array
    {
        return self::$profiles;
    }

    public function getProfile(string $name): ?Extension
    {
        return self::$profiles[$name] ?? null;
    }

    /**
     * @param array<int, Extension> $modules
     * @param array<int, Extension> $themes
     * @param array<int, Extension> $profiles
     */
    public function setExtensions(array $modules, array $themes, array $profiles): void
    {
        self::$modules = self::keyByExtensionName($modules);
        self::$themes = self::keyByExtensionName($themes);
        self::$profiles = self::keyByExtensionName($profiles);
    }

    /**
     * @param array<int, Extension> $extensions
     * @return array<string, Extension>
     */
    private static function keyByExtensionName(array $extensions): array
    {
        // PHP 7.4 returns array|false, PHP 8.0 only returns an array.
        // Make PHPStan happy. When PHP 7.4 is dropped, reduce to a single
        // return.
        $combined = array_combine(array_map(static function (Extension $extension) {
            return $extension->getName();
        }, $extensions), $extensions);
        // @phpstan-ignore-next-line
        assert(is_array($combined));
        return $combined;
    }
}
