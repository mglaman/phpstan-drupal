<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback;

use Drupal\Core\Render\PlaceholderGenerator;
use Drupal\Core\Render\Renderer;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use mglaman\PHPStanDrupal\Drupal\ServiceResultCacheValueExtension;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;
use function explode;
use function in_array;
use function ltrim;
use function preg_match;
use function substr_count;

/**
 * Finds the callbacks in a render array item and resolves their types.
 *
 * @see https://www.drupal.org/node/2966725
 */
final class RenderCallbackResolver
{

    private const RENDER_KEYS = [
        '#pre_render',
        '#post_render',
        '#access_callback',
        '#lazy_builder',
        '#date_time_callbacks',
        '#date_date_callbacks',
    ];

    private ReflectionProvider $reflectionProvider;

    private ServiceMap $serviceMap;

    public function __construct(ReflectionProvider $reflectionProvider, ServiceMap $serviceMap)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->serviceMap = $serviceMap;
    }

    /**
     * Returns the render key of an item that holds callbacks, or NULL.
     */
    public function findRenderKey(ArrayItem $node, Scope $scope): ?string
    {
        $key = $node->key;
        if (!$key instanceof String_ || !in_array($key->value, self::RENDER_KEYS, true)) {
            return null;
        }
        // A key filter, like array_intersect_key($elements, ['#lazy_builder' => TRUE]),
        // is not a render array.
        if ($key->value === '#lazy_builder'
            && ($this->isInLazyBuilderKeyFilter($scope) || $scope->getType($node->value)->isBoolean()->yes())
        ) {
            return null;
        }
        return $key->value;
    }

    /**
     * Returns the callback expressions keyed by their position.
     *
     * @return array<int, Expr>|null
     *   NULL when the value does not have the shape the render key expects.
     */
    public function findCallbackExpressions(string $renderKey, Expr $value): ?array
    {
        if ($renderKey === '#access_callback') {
            return [0 => $value];
        }
        if (!$value instanceof Array_) {
            return null;
        }
        if ($renderKey === '#lazy_builder') {
            return count($value->items) === 0 ? [] : [0 => $value->items[0]->value];
        }
        $callbacks = [];
        foreach ($value->items as $position => $item) {
            $callbacks[$position] = $item->value;
        }
        return $callbacks;
    }

    // @todo move to a helper, as Drupal uses `service:method` references a lot.
    /**
     * Resolves a callback to a callable array type where possible.
     *
     * @param Scope&DependencyTracker $scope
     */
    public function resolveType(Expr $node, Scope $scope): Type
    {
        $type = $scope->getType($node);
        // Covers concatenation of static::class . '::methodName'.
        if ($node instanceof Concat) {
            $leftType = $scope->getType($node->left);
            $rightType = $scope->getType($node->right);
            $rightConstantStrings = $rightType->getConstantStrings();
            if (count($rightConstantStrings) > 0 && $leftType->isClassString()->yes() && $leftType->getClassStringObjectType() instanceof StaticType) {
                return new ConstantArrayType(
                    [new ConstantIntegerType(0), new ConstantIntegerType(1)],
                    [
                        $leftType->getClassStringObjectType(),
                        new ConstantStringType(ltrim($rightConstantStrings[0]->getValue(), ':'))
                    ]
                );
            }
        }

        $constantStrings = $type->getConstantStrings();
        if (count($constantStrings) > 0) {
            $constantString = $constantStrings[0];
            if ($constantString->isClassString()->yes()) {
                return $type;
            }
            // Covers \Drupal\Core\Controller\ControllerResolver::createController.
            if (substr_count($constantString->getValue(), ':') === 1) {
                [$class_or_service, $method] = explode(':', $constantString->getValue(), 2);

                $serviceClass = $this->resolveServiceClass($class_or_service, $scope);
                if ($serviceClass === null) {
                    return $type;
                }
                return new ConstantArrayType(
                    [new ConstantIntegerType(0), new ConstantIntegerType(1)],
                    [
                        new ObjectType($serviceClass),
                        new ConstantStringType($method)
                    ]
                );
            }
            // @see \PHPStan\Type\Constant\ConstantStringType::isCallable
            preg_match('#^([a-zA-Z_\\x7f-\\xff\\\\][a-zA-Z0-9_\\x7f-\\xff\\\\]*)::([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)\\z#', $constantString->getValue(), $matches);
            if (count($matches) === 3 && $this->reflectionProvider->hasClass($matches[1])) {
                return new ConstantArrayType(
                    [new ConstantIntegerType(0), new ConstantIntegerType(1)],
                    [
                        new StaticType($this->reflectionProvider->getClass($matches[1])),
                        new ConstantStringType($matches[2])
                    ]
                );
            }
        }

        return $type;
    }

    /**
     * Returns the class of a service in the service map, or NULL.
     *
     * @param Scope&DependencyTracker $scope
     */
    public function resolveServiceClass(string $serviceId, Scope $scope): ?string
    {
        $scope->trackValueDependency(ServiceResultCacheValueExtension::class, $serviceId);
        return $this->serviceMap->getService($serviceId)?->getClass();
    }

    /**
     * These classes use #lazy_builder as a key in array_intersect_key().
     *
     * With PHPStan 1.6, nodes do not track their parent/next/prev which saves
     * a lot of memory. But makes it harder to detect if we're in a call to
     * array_intersect_key. This is an easier workaround.
     */
    private function isInLazyBuilderKeyFilter(Scope $scope): bool
    {
        if (!$scope->isInClass()) {
            return false;
        }
        $classType = new ObjectType($scope->getClassReflection()->getName());
        $allowedTypes = new UnionType([
            new ObjectType(PlaceholderGenerator::class),
            new ObjectType(Renderer::class),
            new ObjectType('Drupal\Tests\Core\Render\RendererPlaceholdersTest'),
        ]);
        return $allowedTypes->isSuperTypeOf($classType)->yes();
    }
}
