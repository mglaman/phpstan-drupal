<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Classes;

use PhpParser\Node\Stmt\Class_;
use Drupal\Core\Plugin\DefaultPluginManager;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use ReflectionClass;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

class PluginManagerInspectionRule implements Rule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Class_);

        if ($node->extends === null) {
            return [];
        }

        // If the class does not extend the default plugin manager, skip it.
        // @todo inspect interfaces and see if it implements PluginManagerInterface.
        if ($node->extends->toString() !== DefaultPluginManager::class) {
            return [];
        }

        $errors = [];
        if ($this->isYamlDiscovery($node)) {
            $errors = $this->inspectYamlPluginManager($node);
        } else {
            // @todo inspect annotated plugin managers.
        }

        $hasAlterInfoSet = false;

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $stmt->name->toString() === '__construct') {
                foreach ($stmt->stmts ?? [] as $statement) {
                    if ($statement instanceof Expression) {
                        $statement = $statement->expr;
                    }
                    if ($statement instanceof MethodCall
                        && $statement->name instanceof Identifier
                        && $statement->name->name === 'alterInfo') {
                        $hasAlterInfoSet = true;
                    }
                }
            }
        }

        if (!$hasAlterInfoSet) {
            $errors[] = 'Plugin definitions cannot be altered.';
        }

        return $errors;
    }

    private function isYamlDiscovery(Class_ $class): bool
    {
        foreach ($class->stmts as $stmt) {
            // YAML discovery plugin managers must override getDiscovery.
            if ($stmt instanceof ClassMethod && $stmt->name->toString() === 'getDiscovery') {
                foreach ($stmt->stmts ?? [] as $methodStmt) {
                    if ($methodStmt instanceof If_) {
                        foreach ($methodStmt->stmts as $ifStmt) {
                            if ($ifStmt instanceof Expression) {
                                $ifStmtExpr = $ifStmt->expr;
                                if ($ifStmtExpr instanceof Assign) {
                                    $ifStmtExprVar = $ifStmtExpr->var;
                                    if ($ifStmtExprVar instanceof PropertyFetch
                                        && $ifStmtExprVar->var instanceof Variable
                                        && $ifStmtExprVar->name instanceof Identifier
                                        && $ifStmtExprVar->name->name === 'discovery'
                                    ) {
                                        $ifStmtExprExpr = $ifStmtExpr->expr;
                                        if ($ifStmtExprExpr instanceof New_
                                            && ($ifStmtExprExpr->class instanceof Name)
                                            && $ifStmtExprExpr->class->toString() === YamlDiscovery::class) {
                                            return true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    private function inspectYamlPluginManager(Class_ $class): array
    {
        $errors = [];

        $fqn = $class->namespacedName;
        $reflection = new ReflectionClass($fqn);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            throw new ShouldNotHappenException();
        }

        if ($constructor->class !== $fqn->toString()) {
            $errors[] = sprintf('%s must override __construct if using YAML plugins.', $fqn);
        } else {
            foreach ($class->stmts as $stmt) {
                if ($stmt instanceof ClassMethod && $stmt->name->toString() === '__construct') {
                    foreach ($stmt->stmts ?? [] as $constructorStmt) {
                        if ($constructorStmt instanceof Expression) {
                            $constructorStmt = $constructorStmt->expr;
                        }
                        if ($constructorStmt instanceof StaticCall
                            && $constructorStmt->class instanceof Name
                            && ((string)$constructorStmt->class === 'parent')
                            && $constructorStmt->name instanceof Identifier
                            && $constructorStmt->name->name === '__construct') {
                            $errors[] = sprintf('YAML plugin managers should not invoke its parent constructor.');
                        }
                    }
                }
            }
        }
        return $errors;
    }
}
