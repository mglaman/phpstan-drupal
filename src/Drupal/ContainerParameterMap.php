<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/**
 * Types of the container parameters that services.yml files define.
 *
 * The values in services.yml files are defaults. A site's services.yml
 * overrides them, and the kernel and compiler passes replace some, such as
 * app.root and cache_default_bin_backends. So a type keeps the kind of value
 * and drops the value itself: '' is a string, not ''.
 *
 * Reads from DrupalSite, so it works before the bootstrap file runs.
 */
final class ContainerParameterMap
{

    /** @var array<string, Type|null> */
    private array $types = [];

    public function __construct(
        private readonly DrupalSite $site
    ) {
    }

    /**
     * The type of a parameter's value, or null when it isn't known.
     *
     * A parameter that no services.yml file defines can still come from the
     * site's services.yml or a service provider, so it is not known either.
     */
    public function getType(string $name): ?Type
    {
        if (!array_key_exists($name, $this->types)) {
            $this->types[$name] = $this->resolve($this->site->getServiceParameters()[$name] ?? []);
        }
        return $this->types[$name];
    }

    /**
     * @param list<mixed> $values
     */
    private function resolve(array $values): ?Type
    {
        if ($values === []) {
            return null;
        }
        $types = [];
        foreach ($values as $value) {
            // A null default, like password.algorithm, leaves the value to
            // the site, so it says nothing about the type.
            $type = self::typeOf($value);
            if ($type === null) {
                return null;
            }
            $types[] = $type;
        }
        return TypeCombinator::union(...$types);
    }

    private static function typeOf(mixed $value): ?Type
    {
        if (is_string($value)) {
            return new StringType();
        }
        if (is_int($value)) {
            return new IntegerType();
        }
        if (is_float($value)) {
            return new FloatType();
        }
        if (is_bool($value)) {
            return new BooleanType();
        }
        if (is_array($value)) {
            return new ArrayType(new MixedType(), new MixedType());
        }
        return null;
    }
}
