<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\PluginManager;

use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\ShouldNotHappenException;

class PluginManagerSetsCacheBackendRule extends AbstractPluginManagerRule
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     * @return string[]
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof ClassMethod);

        if (!$scope->isInClass()) {
            throw new ShouldNotHappenException();
        }

        if ($scope->isInTrait()) {
            return [];
        }

        if ($node->name->name !== '__construct') {
            return [];
        }

        $scopeClassReflection = $scope->getClassReflection();

        if ($scopeClassReflection === null) {
            throw new ShouldNotHappenException();
        }

        $classReflection = $scopeClassReflection->getNativeReflection();

        if (!$this->isPluginManager($classReflection)) {
            return [];
        }

        $hasCacheBackendSet = false;
        $misnamedCacheTagWarnings = [];

        foreach ($node->stmts ?? [] as $statement) {
            if ($statement instanceof Expression) {
                $statement = $statement->expr;
            }
            if (($statement instanceof MethodCall) &&
                ($statement->name instanceof Identifier) &&
                $statement->name->name === 'setCacheBackend') {
                // setCacheBackend accepts a cache backend, the cache key, and optional (but suggested) cache tags.
                $setCacheBackendArgs = $statement->args;

                if ($setCacheBackendArgs[1] instanceof VariadicPlaceholder) {
                    throw new ShouldNotHappenException();
                }
                $cacheKey = $setCacheBackendArgs[1]->value;
                if (!$cacheKey instanceof String_) {
                    continue;
                }
                $hasCacheBackendSet = true;

                if (isset($setCacheBackendArgs[2])) {
                    if ($setCacheBackendArgs[2] instanceof VariadicPlaceholder) {
                        throw new ShouldNotHappenException();
                    }
                    /** @var Array_ $cacheTags */
                    $cacheTags = $setCacheBackendArgs[2]->value;
                    if (count($cacheTags->items) > 0) {
                        /** @var ArrayItem $item */
                        foreach ($cacheTags->items as $item) {
                            if (($item->value instanceof String_) &&
                                strpos($item->value->value, $cacheKey->value) === false) {
                                $misnamedCacheTagWarnings[] = $item->value->value;
                            }
                        }
                    }
                }

                break;
            }
        }

        $errors = [];
        if (!$hasCacheBackendSet) {
            $errors[] = 'Missing cache backend declaration for performance.';
        }
        foreach ($misnamedCacheTagWarnings as $cacheTagWarning) {
            $errors[] = sprintf('%s cache tag might be unclear and does not contain the cache key in it.', $cacheTagWarning);
        }

        return $errors;
    }
}
