<?php declare(strict_types=1);

namespace PHPStan\Rules\Drupal\PluginManager;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;

class PluginManagerSetsCacheBackendRule extends AbstractPluginManagerRule
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param Node $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return string[]
     * @throws \PHPStan\ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Stmt\ClassMethod);

        if (!$scope->isInClass()) {
            throw new \PHPStan\ShouldNotHappenException();
        }

        if ($scope->isInTrait()) {
            return [];
        }

        if ($node->name->name !== '__construct') {
            return [];
        }

        $classReflection = $scope->getClassReflection()->getNativeReflection();

        if (!$this->isPluginManager($classReflection)) {
            return [];
        }

        $hasCacheBackendSet = false;
        $hasCacheTags = false;
        $misnamedCacheTagWarnings = [];

        foreach ($node->stmts as $statement) {
            if ($statement instanceof Node\Stmt\Expression) {
                $statement = $statement->expr;
            }
            if (($statement instanceof Node\Expr\MethodCall) && (string)$statement->name === 'setCacheBackend') {
                $hasCacheBackendSet = true;

                // setCacheBackend accepts a cache backend, the cache key, and optional (but suggested) cache tags.
                $setCacheBackendArgs = $statement->args;

                $cacheKey = $setCacheBackendArgs[1]->value->value;
                if (isset($setCacheBackendArgs[2])) {
                    /** @var \PhpParser\Node\Expr\Array_ $cacheTags */
                    $cacheTags = $setCacheBackendArgs[2]->value;
                    if (!empty($cacheTags->items)) {
                        $hasCacheTags = true;
                        foreach ($cacheTags->items as $item) {
                            if (
                                ($item->value instanceof Node\Scalar\String_) &&
                                strpos($item->value->value, $cacheKey) === false) {
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
        if (!$hasCacheTags) {
            $errors[] = 'Plugin manager has cache backend specified but does not declare cache tags.';
        }
        foreach ($misnamedCacheTagWarnings as $cacheTagWarning) {
            $errors[] = sprintf('%s cache tag might be unclear and does not contain the cache key in it.', $cacheTagWarning);
        }

        return $errors;
    }

}
