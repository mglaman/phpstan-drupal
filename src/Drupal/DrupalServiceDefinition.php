<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function str_replace;

class DrupalServiceDefinition
{

    private const DEFAULT_DEPRECATION_TEMPLATE = 'The "%service_id%" service is deprecated. You should stop using it, as it will soon be removed.';

    private bool $deprecated = false;

    private ?string $deprecationTemplate = null;

    /**
     * @var array<string, \mglaman\PHPStanDrupal\Drupal\DrupalServiceDefinition>
     */
    private array $decorators = [];

    public function __construct(
        private readonly string $id,
        private readonly ?string $class,
        private readonly bool $public = true,
        private readonly ?string $alias = null
    ) {
    }

    public function setDeprecated(bool $status = true, ?string $template = null): void
    {
        $this->deprecated = $status;
        $this->deprecationTemplate = $template;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

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
        return str_replace('%service_id%', $this->id, $this->deprecationTemplate ?? self::DEFAULT_DEPRECATION_TEMPLATE);
    }

    public function getType(): Type
    {
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
