<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\PluginManager;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;

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

        $scopeClassReflection = $scope->getClassReflection();

        if (!$this->isPluginManager($scopeClassReflection)) {
            return [];
        }

        $hasCacheBackendSet = false;
        $misnamedCacheTagWarnings = [];

        foreach ($node->stmts ?? [] as $statement) {
            if ($statement instanceof Node\Stmt\Expression) {
                $statement = $statement->expr;
            }
            if (($statement instanceof Node\Expr\MethodCall) &&
                ($statement->name instanceof Node\Identifier) &&
                $statement->name->name === 'setCacheBackend') {
                // setCacheBackend accepts a cache backend, the cache key, and optional (but suggested) cache tags.
                $setCacheBackendArgs = $statement->getArgs();
                if (count($setCacheBackendArgs) < 2) {
                    continue;
                }
                $hasCacheBackendSet = true;

                $cacheKey = array_map(
                    static fn (Type $type) => $type->getValue(),
                    $scope->getType($setCacheBackendArgs[1]->value)->getConstantStrings()
                );
                if (count($cacheKey) === 0) {
                    continue;
                }

                if (isset($setCacheBackendArgs[2])) {
                    $cacheTagsType = $scope->getType($setCacheBackendArgs[2]->value);
                    foreach ($cacheTagsType->getConstantArrays() as $constantArray) {
                        foreach ($constantArray->getValueTypes() as $valueType) {
                            foreach ($valueType->getConstantStrings() as $cacheTagConstantString) {
                                foreach ($cacheKey as $cacheKeyValue) {
                                    if (strpos($cacheTagConstantString->getValue(), $cacheKeyValue) === false) {
                                        $misnamedCacheTagWarnings[] = $cacheTagConstantString->getValue();
                                    }
                                }
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
