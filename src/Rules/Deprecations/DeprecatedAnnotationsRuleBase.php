<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Deprecations;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;

/**
 * @implements Rule<Node\Stmt\Class_>
 */
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

    protected function isAnnotated(ResolvedPhpDocBlock $phpDoc, string $annotationId): bool
    {
        foreach ($phpDoc->getPhpDocNodes() as $docNode) {
            foreach ($docNode->children as $childNode) {
                if (($childNode instanceof PhpDocTagNode) && $childNode->name === $annotationId) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function getPluginAttribute(ClassReflection $reflection, string $attributeName): ?ReflectionAttribute
    {
        try {
            $nativeReflection = new ReflectionClass($reflection->getName());
            $attribute = $nativeReflection->getAttributes($attributeName);
        } catch (ReflectionException) {
            return null;
        }

        return $attribute[0] ?? null;
    }
}
