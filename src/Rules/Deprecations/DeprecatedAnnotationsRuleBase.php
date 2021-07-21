<?php declare(strict_types=1);

namespace PHPStan\Rules\Deprecations;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;

abstract class DeprecatedAnnotationsRuleBase implements Rule
{

    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    protected $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Node\Stmt\Class_::class;
    }

    abstract protected function getExpectedInterface(): string;

    abstract protected function doProcessNode(
        ClassReflection $reflection,
        Node\Stmt\Class_ $node,
        Scope $scope
    ): array;

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Stmt\Class_);
        if ($node->extends === null) {
            return [];
        }
        if ($node->name === null) {
            return [];
        }
        if ($node->isAbstract()) {
            return [];
        }
        // PHPStan gives anonymous classes a name, so we cannot determine if
        // a class is truly anonymous using the normal methods from php-parser.
        // @see \PHPStan\Reflection\BetterReflection\BetterReflectionProvider::getAnonymousClassReflection
        if ($node->hasAttribute('anonymousClass') && $node->getAttribute('anonymousClass') === true) {
            return [];
        }
        $className = $node->name->name;
        $namespace = $scope->getNamespace();
        $reflection = $this->reflectionProvider->getClass($namespace . '\\' . $className);
        $implementsExpectedInterface = $reflection->implementsInterface($this->getExpectedInterface());
        if (!$implementsExpectedInterface) {
            return [];
        }

        return $this->doProcessNode($reflection, $node, $scope);
    }
}
