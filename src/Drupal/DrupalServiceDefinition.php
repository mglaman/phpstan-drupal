<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class DrupalServiceDefinition
{

    private string $id;

    private ?string $class;

    private bool $public;

    private bool $deprecated = false;

    private ?string $deprecationTemplate = null;

    private static string $defaultDeprecationTemplate = 'The "%service_id%" service is deprecated. You should stop using it, as it will soon be removed.';

    private ?string $alias;

    public function __construct(string $id, ?string $class, bool $public = true, ?string $alias = null)
    {
        $this->id = $id;
        $this->class = $class;
        $this->public = $public;
        $this->alias = $alias;
    }

    public function setDeprecated(bool $status = true, ?string $template = null): void
    {
        $this->deprecated = $status;
        $this->deprecationTemplate = $template;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->public;
    }

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }

    public function getDeprecatedDescription(): string
    {
        return str_replace('%service_id%', $this->id, $this->deprecationTemplate ?? self::$defaultDeprecationTemplate);
    }

    public function getType(): Type
    {
        // Work around Drupal misusing the SplString class for string
        // pseudo-services such as 'app.root'.
        // @see https://www.drupal.org/project/drupal/issues/3074585
        if ($this->getClass() === 'SplString') {
            return new StringType();
        }

        return new ObjectType($this->getClass() ?? $this->id);
    }
}
