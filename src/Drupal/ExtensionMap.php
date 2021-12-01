<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

final class ExtensionMap
{
    /** @var Extension[]  */
    private static $modules = [];

    /** @var Extension[]  */
    private static $themes = [];

    /** @var Extension[]  */
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
     * @param Extension[] $modules
     * @param Extension[] $themes
     * @param Extension[] $profiles
     */
    public function setExtensions(array $modules, array $themes, array $profiles): void
    {
        self::$modules = $modules;
        self::$themes = $themes;
        self::$profiles = $profiles;
    }
}
