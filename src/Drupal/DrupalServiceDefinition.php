<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function str_replace;

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
     * @var bool
     */
    private $deprecated = false;

    /**
     * @var string|null
     */
    private $deprecationTemplate;

    /**
     * @var string
     */
    private static $defaultDeprecationTemplate = 'The "%service_id%" service is deprecated. You should stop using it, as it will soon be removed.';

    /**
     * @var string|null
     */
    private $alias;

    /**
     * @var array<string, \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition>
     */
    private $decorators = [];

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

        $decorating_services = $this->getDecorators();
        if (count($decorating_services) !== 0) {
            $combined_services = [];
            $combined_services[] = new ObjectType($this->getClass() ?? $this->id);
            foreach ($decorating_services as $service_id => $service_definition) {
                $combined_services[] = $service_definition->getType();
            }
            return TypeCombinator::union(...$combined_services);
        }
        return new ObjectType($this->getClass() ?? $this->id);
    }

    public function addDecorator(DrupalServiceDefinition $definition): void
    {
        $this->decorators[$definition->getId()] = $definition;
    }

    /**
     * @return array<string, \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition>
     */
    public function getDecorators(): array
    {
        return $this->decorators;
    }
}
