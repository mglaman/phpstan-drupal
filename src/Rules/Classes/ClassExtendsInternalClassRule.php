<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Classes;

use mglaman\PHPStanDrupal\Internal\NamespaceCheck;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

class ClassExtendsInternalClassRule implements Rule
{
    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        /** @var Class_ $node */
        if (!isset($node->extends)) {
            return [];
        }

        $extendedClassName = $node->extends->toString();
        if (!$this->reflectionProvider->hasClass($extendedClassName)) {
            return [];
        }

        $extendedClassReflection = $this->reflectionProvider->getClass($extendedClassName);
        if (!$extendedClassReflection->isInternal()) {
            return [];
        }

        // @phpstan-ignore-next-line
        if (!isset($node->namespacedName)) {
            return $this->buildError(null, $extendedClassName);
        }

        $currentClassName = $node->namespacedName->toString();

        if (!NamespaceCheck::isDrupalNamespace($node)) {
            return $this->buildError($currentClassName, $extendedClassName);
        }

        if (NamespaceCheck::isSharedNamespace($node)) {
            return [];
        }

        return $this->buildError($currentClassName, $extendedClassName);
    }

    private function buildError(?string $currentClassName, string $extendedClassName): array
    {
        return [
            RuleErrorBuilder::message(\sprintf(
                '%s extends @internal class %s.',
                $currentClassName !== null ? \sprintf('Class %s', $currentClassName) : 'Anonymous class',
                $extendedClassName
            ))->build()
        ];
    }
}
