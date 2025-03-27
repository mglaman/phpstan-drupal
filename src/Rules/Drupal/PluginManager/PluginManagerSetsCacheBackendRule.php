<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\PluginManager;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use function array_map;
use function count;

/**
 * @extends AbstractPluginManagerRule<ClassMethod>
 */
class PluginManagerSetsCacheBackendRule extends AbstractPluginManagerRule
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
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

        if (!$this->isPluginManager($scopeClassReflection)) {
            return [];
        }

        $hasCacheBackendSet = false;

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

                break;
            }
        }

        $errors = [];
        if (!$hasCacheBackendSet) {
            $errors[] = 'Missing cache backend declaration for performance.';
        }

        return $errors;
    }
}
