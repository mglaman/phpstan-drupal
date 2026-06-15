<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_shift;
use function explode;
use function in_array;
use function is_array;
use function is_string;

class ConfigSchemaData
{
    /**
     * Raw schema definitions keyed by schema type name.
     *
     * @var array<string, array<string, mixed>>
     */
    private static $definitions = [];

    /**
     * Config names that have FullyValidatable constraint.
     *
     * @var array<string, bool>
     */
    private static $fullyValidatable = [];

    /**
     * @param array<string, array<string, mixed>> $definitions
     * @param array<string, bool> $fullyValidatable
     */
    public function setSchema(array $definitions, array $fullyValidatable): void
    {
        self::$definitions = $definitions;
        self::$fullyValidatable = $fullyValidatable;
    }

    public function isFullyValidatable(string $configName): bool
    {
        return self::$fullyValidatable[$configName] ?? false;
    }

    /**
     * Returns true if the given key path exists in the schema for a FullyValidatable config.
     */
    public function keyExistsInSchema(string $configName, string $key): bool
    {
        if (!$this->isFullyValidatable($configName)) {
            // Can't validate keys for non-FullyValidatable configs.
            return true;
        }

        $definition = self::$definitions[$configName] ?? null;
        if ($definition === null) {
            return true;
        }

        $definition = $this->resolveDefinition($definition);
        $parts = explode('.', $key);
        return $this->keyExistsInDefinition($definition, $parts);
    }

    /**
     * @param array<string, mixed> $definition
     * @param string[] $parts
     */
    private function keyExistsInDefinition(array $definition, array $parts): bool
    {
        $definition = $this->resolveDefinition($definition);

        if ($parts === []) {
            return true;
        }

        if (!isset($definition['mapping']) || !is_array($definition['mapping'])) {
            return false;
        }

        $currentKey = array_shift($parts);
        if (!array_key_exists($currentKey, $definition['mapping'])) {
            return false;
        }

        $childDef = $definition['mapping'][$currentKey];
        if (!is_array($childDef)) {
            return false;
        }

        return $this->keyExistsInDefinition($childDef, $parts);
    }

    public function getTypeForKey(string $configName, string $key): ?Type
    {
        if (!$this->isFullyValidatable($configName)) {
            return null;
        }

        $definition = self::$definitions[$configName] ?? null;
        if ($definition === null) {
            return null;
        }

        // Resolve the base type if this definition uses `type:`.
        $definition = $this->resolveDefinition($definition);

        // Traverse dotted key path through nested mappings.
        $parts = explode('.', $key);
        $type = $this->resolveKeyType($definition, $parts);
        if ($type === null) {
            return null;
        }

        // Config::get() can always return null when a key is absent.
        return new UnionType([$type, new NullType()]);
    }

    /**
     * Resolve a definition that uses `type:` to reference another definition.
     *
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function resolveDefinition(array $definition, int $depth = 0): array
    {
        if ($depth > 10) {
            return $definition;
        }

        if (isset($definition['type']) && is_string($definition['type'])) {
            $typeName = $definition['type'];
            $parent = self::$definitions[$typeName] ?? null;
            if ($parent !== null) {
                $resolved = $this->resolveDefinition($parent, $depth + 1);
                // Merge: child overrides parent.
                $definition = $this->mergeDefinitions($resolved, $definition);
            }
        }

        return $definition;
    }

    /**
     * @param array<string, mixed> $parent
     * @param array<string, mixed> $child
     * @return array<string, mixed>
     */
    private function mergeDefinitions(array $parent, array $child): array
    {
        $result = $parent;
        foreach ($child as $key => $value) {
            if ($key === 'mapping' && is_array($value) && isset($result['mapping']) && is_array($result['mapping'])) {
                $result['mapping'] = array_merge($result['mapping'], $value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $definition
     * @param string[] $parts
     */
    private function resolveKeyType(array $definition, array $parts): ?Type
    {
        $definition = $this->resolveDefinition($definition);

        if ($parts === []) {
            return $this->mapSchemaTypeToPhpStanType($definition);
        }

        // Must be a mapping to traverse further.
        if (!isset($definition['mapping']) || !is_array($definition['mapping'])) {
            return null;
        }

        $currentKey = array_shift($parts);
        if (!array_key_exists($currentKey, $definition['mapping'])) {
            return null;
        }

        $childDef = $definition['mapping'][$currentKey];
        if (!is_array($childDef)) {
            return null;
        }

        return $this->resolveKeyType($childDef, $parts);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function mapSchemaTypeToPhpStanType(array $definition): ?Type
    {
        $typeName = $definition['type'] ?? null;
        if ($typeName === null) {
            // If it has 'mapping', it's a mapping type.
            if (isset($definition['mapping'])) {
                return new ArrayType(new StringType(), new MixedType());
            }
            return null;
        }

        return $this->scalarTypeFromName($typeName, $definition);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function scalarTypeFromName(string $typeName, array $definition = []): ?Type
    {
        // Direct scalar types.
        if (in_array($typeName, ['string', 'label', 'path', 'uri', 'email', 'color_hex', 'text', 'date_format', 'machine_name', 'langcode', 'uuid', 'required_label', 'plural_label', 'bytes'], true)) {
            return new StringType();
        }
        if (in_array($typeName, ['integer', 'weight', 'timestamp'], true)) {
            return new IntegerType();
        }
        if ($typeName === 'float') {
            return new FloatType();
        }
        if ($typeName === 'boolean') {
            return new BooleanType();
        }
        if ($typeName === 'sequence') {
            // Try to resolve the element type from the sequence definition.
            $elementType = $this->resolveSequenceElementType($definition);
            return new ArrayType(new MixedType(), $elementType);
        }
        if ($typeName === 'mapping') {
            return new ArrayType(new StringType(), new MixedType());
        }

        // Try to resolve via type references.
        $referenced = self::$definitions[$typeName] ?? null;
        if ($referenced !== null) {
            $resolved = $this->resolveDefinition($referenced);
            return $this->mapSchemaTypeToPhpStanType($resolved);
        }

        return null;
    }

    /**
     * Resolve the element type of a sequence definition.
     *
     * @param array<string, mixed> $definition
     */
    private function resolveSequenceElementType(array $definition): Type
    {
        // Drupal sequence schemas define the element type under a `sequence:` key.
        // It can be either a single definition (list) or keyed array.
        if (!isset($definition['sequence']) || !is_array($definition['sequence'])) {
            return new MixedType();
        }

        $sequence = $definition['sequence'];

        // Sequence can be a list (numeric keys) or a single associative definition.
        // When it's a list, take the first item's type.
        if (isset($sequence[0]) && is_array($sequence[0])) {
            $elementDef = $sequence[0];
        } elseif (!isset($sequence[0])) {
            // Single associative definition.
            $elementDef = $sequence;
        } else {
            return new MixedType();
        }

        $elementTypeName = $elementDef['type'] ?? null;
        if (!is_string($elementTypeName)) {
            return new MixedType();
        }

        $resolved = $this->scalarTypeFromName($elementTypeName, $elementDef);
        return $resolved ?? new MixedType();
    }
}
