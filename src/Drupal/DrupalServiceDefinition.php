<?php declare(strict_types=1);

namespace PHPStan\Drupal;

class DrupalServiceDefinition
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string|null
     */
    private $class;

    /**
     * @var bool
     */
    private $public;

    /**
     * @var string|null
     */
    private $alias;

    public function __construct(string $id, ?string $class, bool $public = true, ?string $alias = null)
    {
        $this->id = $id;
        $this->class = $class;
        $this->public = $public;
        $this->alias = $alias;
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
}
