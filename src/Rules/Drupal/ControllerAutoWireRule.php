<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassMethod>
 */
final class ControllerAutoWireRule implements Rule
{

    /**
     * @var ReflectionProvider
     */
    protected $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->isStatic() || !$node->isPublic()) {
            return [];
        }

        if ($node->name->name !== 'create') {
            return [];
        }

        // Get the class node object.
        $class = $node->getAttribute('parent');

        // If class not extends any class, no need to check.
        if ($class->extends === null) {
            return [];
        }

        // If class name is not defined.
        if ($class->name === null) {
            return [];
        }

        $namespace = $class->namespacedName->toCodeString();
        $reflection = $this->reflectionProvider->getClass($namespace);
        $implemented_traits = array_keys($reflection->getTraits(true));

        // If no traits implemented.
        if (!$implemented_traits) {
            return [];
        }

        if (in_array('Drupal\Core\DependencyInjection\AutowireTrait', $implemented_traits)) {
            return [
                RuleErrorBuilder::message(
                    'Controllers are autowired from drupal:10.2.0. Overriding the create() method is no longer required.'
                )->tip('See https://www.drupal.org/node/3395716')->build(),
            ];
        }

        return [];
    }
}
