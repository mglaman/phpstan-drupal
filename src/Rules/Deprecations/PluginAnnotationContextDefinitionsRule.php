<?php declare(strict_types=1);

namespace PHPStan\Rules\Deprecations;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

final class PluginAnnotationContextDefinitionsRule implements Rule
{

    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Node\Stmt\Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Stmt\Class_);
        if ($node->extends === null) {
            return [];
        }
        if ($node->name === null) {
            return [];
        }
        $className = $node->name->name;
        $namespace = $scope->getNamespace();
        $reflection = $this->reflectionProvider->getClass($namespace . '\\' . $className);
        $isContextAwarePlugin = $reflection->implementsInterface('Drupal\Component\Plugin\ContextAwarePluginInterface');
        // We only process context aware plugins.
        if (!$isContextAwarePlugin) {
            return [];
        }
        $annotation = $reflection->getResolvedPhpDoc();
        // Plugins should always be annotated, but maybe this class is missing its
        // annotation since it swaps an existing one.
        if ($annotation === null) {
            return [];
        }
        $hasMatch = strpos($annotation->getPhpDocString(), 'context = {') !== false;
        if ($hasMatch) {
            return [
                'Providing context definitions via the "context" key is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use the "context_definitions" key instead.',
            ];
        }
        return [];
    }
}
