<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback;

use Drupal\Core\Security\Attribute\TrustedCallback;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function array_map;
use function array_merge;
use function class_exists;
use function count;
use function implode;
use function in_array;
use function ksort;
use function sprintf;
use function strtolower;

/**
 * Reports render callback methods trustedCallbacks() does not return.
 *
 * Drupal throws UntrustedCallbackException for these callbacks, unless the
 * method has the #[TrustedCallback] attribute.
 *
 * The rule only reports classes in the analyzed paths whose
 * trustedCallbacks() returns constant strings. For a callback like
 * [$this, 'method'], it checks the class and its analyzed concrete subclasses
 * that do not override the method holding the callback.
 *
 * @phpstan-import-type TrustedCallbackClass from TrustedCallbackClassCollector
 * @phpstan-import-type RenderCallback from RenderCallbackCollector
 * @implements Rule<CollectedDataNode>
 */
final class RenderCallbackTrustedCallbacksRule implements Rule
{

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isOnlyFilesAnalysis()) {
            return [];
        }

        $trustedCallbacksByClass = $this->trustedCallbacksByClass($node);
        $classes = [];
        $concreteSubtypes = [];
        foreach ($node->get(TrustedCallbackClassCollector::class) as $collectedClasses) {
            foreach ($collectedClasses as $class) {
                $classes[$class['name']] = $class;
                if (!$class['concrete']) {
                    continue;
                }
                foreach (array_merge($class['parents'], $class['interfaces']) as $supertype) {
                    $concreteSubtypes[$supertype][$class['name']] = $class['name'];
                }
            }
        }

        $untrustedClassesByCallback = [];
        $callbacks = [];
        foreach ($node->get(RenderCallbackCollector::class) as $collectedCallbacks) {
            foreach ($collectedCallbacks as $callbacksInItem) {
                foreach ($callbacksInItem as $callback) {
                    $callbackKey = sprintf(
                        '%s:%d:%s:%d:%s',
                        $callback['file'],
                        $callback['line'],
                        $callback['renderKey'],
                        $callback['position'],
                        $callback['method']
                    );
                    $callbacks[$callbackKey] = $callback;
                    $untrustedClassesByCallback[$callbackKey] ??= [];

                    foreach ($this->runtimeClasses($callback, $classes, $concreteSubtypes) as $runtimeClass) {
                        $class = $classes[$runtimeClass] ?? null;
                        if ($class === null || in_array($callback['method'], $class['attributeMethods'], true)) {
                            continue;
                        }
                        $trustedCallbacks = $trustedCallbacksByClass[$class['trustedCallbacksClass']] ?? null;
                        if ($trustedCallbacks === null || in_array($callback['method'], $trustedCallbacks, true)) {
                            continue;
                        }
                        $untrustedClassesByCallback[$callbackKey][$runtimeClass] = $runtimeClass;
                    }
                }
            }
        }

        $errors = [];
        foreach ($untrustedClassesByCallback as $callbackKey => $untrustedClasses) {
            if (count($untrustedClasses) === 0) {
                continue;
            }
            $callback = $callbacks[$callbackKey];
            ksort($untrustedClasses);
            $errors[] = RuleErrorBuilder::message(sprintf(
                "%s callback method '%s' at key '%s' is not returned by %s.",
                $callback['renderKey'],
                $callback['method'],
                $callback['position'],
                implode(' and ', array_map(
                    static fn (string $class): string => $class . '::trustedCallbacks()',
                    $untrustedClasses
                ))
            ))
                ->file($callback['file'])
                ->line($callback['line'])
                ->tip($this->tip($callback['method']))
                ->identifier('renderCallback.methodNotInTrustedCallbacks')
                ->build();
        }
        return $errors;
    }

    /**
     * Returns the method names trustedCallbacks() returns, keyed by class.
     *
     * A class with an unknown list maps to NULL.
     *
     * @return array<string, list<string>|null>
     */
    private function trustedCallbacksByClass(CollectedDataNode $node): array
    {
        $trustedCallbacksByClass = [];
        foreach ($node->get(TrustedCallbacksCollector::class) as $collectedTrustedCallbacks) {
            foreach ($collectedTrustedCallbacks as [$class, $methodNames]) {
                // A class name declared twice, such as in test fixtures, has
                // no single list.
                if (array_key_exists($class, $trustedCallbacksByClass) && $trustedCallbacksByClass[$class] !== $methodNames) {
                    $methodNames = null;
                }
                $trustedCallbacksByClass[$class] = $methodNames;
            }
        }
        return $trustedCallbacksByClass;
    }

    /**
     * Returns the classes Drupal may call trustedCallbacks() on.
     *
     * @param RenderCallback $callback
     * @param array<string, TrustedCallbackClass> $classes
     * @param array<string, array<string, string>> $concreteSubtypes
     * @return list<string>
     */
    private function runtimeClasses(array $callback, array $classes, array $concreteSubtypes): array
    {
        if ($callback['namesClass']) {
            return [$callback['class']];
        }
        $runtimeClasses = [];
        if ($classes[$callback['class']]['concrete'] ?? false) {
            $runtimeClasses[] = $callback['class'];
        }
        foreach ($concreteSubtypes[$callback['class']] ?? [] as $subtype) {
            if ($callback['enclosingMethod'] === null
                || $this->inheritsMethod($subtype, $callback['class'], $callback['enclosingMethod'], $classes)
            ) {
                $runtimeClasses[] = $subtype;
            }
        }
        return $runtimeClasses;
    }

    /**
     * Whether a subclass runs the method of an ancestor without overriding it.
     *
     * Returns FALSE when a class in between is not analyzed.
     *
     * @param array<string, TrustedCallbackClass> $classes
     */
    private function inheritsMethod(string $subclass, string $ancestor, string $method, array $classes): bool
    {
        $method = strtolower($method);
        $class = $classes[$subclass] ?? null;
        while ($class !== null && $class['name'] !== $ancestor) {
            if (in_array($method, $class['declaredMethods'], true)) {
                return false;
            }
            $class = $classes[$class['parents'][0] ?? ''] ?? null;
        }
        return $class !== null;
    }

    private function tip(string $method): string
    {
        if (!class_exists(TrustedCallback::class)) {
            return sprintf("Add '%s' to the array trustedCallbacks() returns. Change record: https://www.drupal.org/node/2966725.", $method);
        }
        return sprintf(
            "Add '%s' to the array trustedCallbacks() returns, or add the #[\\Drupal\\Core\\Security\\Attribute\\TrustedCallback] attribute to the method. Change record: https://www.drupal.org/node/2966725.",
            $method
        );
    }
}
