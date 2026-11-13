<?php

namespace LazyBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

final class Builder implements TrustedCallbackInterface
{

    public function __construct(private EntityInterface $entity)
    {
    }

    public static function trustedCallbacks(): array
    {
        return ['build', 'buildString', 'buildUntyped'];
    }

    public static function build(int $id): array
    {
        return [];
    }

    public static function buildString(int $id): string
    {
        return '';
    }

    public static function buildUntyped($id, $label = null, $weight = null, $enabled = null)
    {
        return [];
    }

    public function errors(mixed $unknown, ?int $maybeId): array
    {
        return [
            'placeholder_without_callback' => [
                '#markup' => 'Recent content',
                '#create_placeholder' => true,
            ],
            'one_value' => [
                '#lazy_builder' => [static::class . '::build'],
            ],
            'arguments_not_an_array' => [
                '#lazy_builder' => [static::class . '::build', 'not an array'],
            ],
            'string_lazy_builder' => [
                '#lazy_builder' => static::class . '::build',
            ],
            'non_scalar_arguments' => [
                '#lazy_builder' => [static::class . '::build', [$this->entity, [1, 2], $unknown, $maybeId]],
            ],
            'unsupported_keys' => [
                '#lazy_builder' => [static::class . '::build', [1]],
                '#title' => 'Recent content',
                'child' => ['#markup' => 'Child'],
            ],
            'non_array_return' => [
                '#lazy_builder' => [static::class . '::buildString', [1]],
            ],
        ];
    }

    public function valid(array $extra): array
    {
        $built = [
            '#create_placeholder' => true,
        ];
        $built['#lazy_builder'] = [static::class . '::build', [1]];

        return [
            'full' => [
                '#lazy_builder' => [static::class . '::build', [1]],
                '#cache' => ['keys' => ['recent']],
                '#create_placeholder' => true,
                '#lazy_builder_preview' => ['#markup' => 'Loading'],
                '#placeholder_strategy_denylist' => [],
                '#preview' => ['#markup' => 'Loading'],
                '#weight' => 10,
            ],
            'untyped_callback' => [
                '#lazy_builder' => [static::class . '::buildUntyped', ['1', null, 1.5, true]],
            ],
            'placeholder_false' => [
                '#markup' => 'Recent content',
                '#create_placeholder' => false,
            ],
            'unknown_keys' => [
                '#lazy_builder' => [static::class . '::build', [1]],
                ...$extra,
            ],
            'built' => $built,
        ];
    }

    public static function buildNever(int $id): never
    {
        throw new \RuntimeException('Redirect');
    }

    public function silent(array $elements, array $build): array
    {
        $filtered = array_intersect_key($elements, ['#lazy_builder' => true, '#cache' => true]);
        $build['block'] += [
            '#create_placeholder' => true,
        ];
        $build['never'] = [
            '#lazy_builder' => [static::class . '::buildNever', [1]],
        ];
        return [$filtered, $build];
    }

    /**
     * @param array<int, EntityInterface> $entities
     */
    public function reviewCases(array $entities): array
    {
        return [
            'null_lazy_builder_with_placeholder' => [
                '#lazy_builder' => null,
                '#markup' => 'Recent content',
                '#create_placeholder' => true,
            ],
            'typed_arguments' => [
                '#lazy_builder' => [static::class . '::build', $entities],
            ],
            'integer_keys' => [
                '#lazy_builder' => [static::class . '::build', [1]],
                0 => ['#markup' => 'First'],
                ['#markup' => 'Second'],
            ],
            'null_lazy_builder' => [
                '#lazy_builder' => null,
                '#markup' => 'Recent content',
            ],
            'reversed_keys' => [
                '#lazy_builder' => [1 => [1], 0 => static::class . '::build'],
            ],
        ];
    }

    public static function buildTwo(int $node_id, string $view_mode): array
    {
        return [];
    }

    public static function buildOptional(int $node_id, string $view_mode = 'full'): array
    {
        return [];
    }

    public static function buildVariadic(int $node_id, int ...$ids): array
    {
        return [];
    }

    /**
     * @param positive-int $node_id
     */
    public static function buildPhpDocType(int $node_id): array
    {
        return [];
    }

    public function argumentErrors(): array
    {
        return [
            'too_few' => [
                '#lazy_builder' => [static::class . '::buildTwo', [1]],
            ],
            'too_few_with_optional' => [
                '#lazy_builder' => [static::class . '::buildOptional', []],
            ],
            'too_many' => [
                '#lazy_builder' => [static::class . '::build', [1, 2]],
            ],
            'too_many_with_optional' => [
                '#lazy_builder' => [static::class . '::buildOptional', [1, 'full', 3]],
            ],
            'not_coercible' => [
                '#lazy_builder' => [static::class . '::buildTwo', ['teaser', 1]],
            ],
            'null_for_int' => [
                '#lazy_builder' => [static::class . '::build', [null]],
            ],
            'variadic_type' => [
                '#lazy_builder' => [static::class . '::buildVariadic', [1, 2, 'three']],
            ],
            'string_keys' => [
                '#lazy_builder' => [static::class . '::buildTwo', ['view_mode' => 'teaser', 'node_id' => 1]],
            ],
        ];
    }

    public function validArguments(?int $maybeId, mixed $unknown): array
    {
        return [
            'numeric_string' => [
                '#lazy_builder' => [static::class . '::build', ['5']],
            ],
            'coerced_to_string' => [
                '#lazy_builder' => [static::class . '::buildTwo', [1, 2]],
            ],
            'optional_omitted' => [
                '#lazy_builder' => [static::class . '::buildOptional', [1]],
            ],
            'variadic_extra' => [
                '#lazy_builder' => [static::class . '::buildVariadic', [1, 2, 3]],
            ],
            'string_keys_in_order' => [
                '#lazy_builder' => [static::class . '::buildTwo', ['node_id' => 1, 'view_mode' => 'teaser']],
            ],
            'maybe_null' => [
                '#lazy_builder' => [static::class . '::build', [$maybeId]],
            ],
            'mixed' => [
                '#lazy_builder' => [static::class . '::buildTwo', [$unknown, $unknown]],
            ],
            'phpdoc_type_only' => [
                '#lazy_builder' => [static::class . '::buildPhpDocType', [0]],
            ],
            'func_get_args' => [
                '#lazy_builder' => [static::class . '::buildFromAllArguments', [1, 2]],
            ],
        ];
    }

    public static function buildFromAllArguments(): array
    {
        return ['#markup' => implode(',', func_get_args())];
    }
}
