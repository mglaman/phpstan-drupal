<?php declare(strict_types=1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;

class PluginManagerInspectionRule implements Rule
{
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

        // If the class does not extend the default plugin manager, skip it.
        // @todo inspect interfaces and see if it implements PluginManagerInterface.
        if ($node->extends->toString() !== 'Drupal\Core\Plugin\DefaultPluginManager') {
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
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === '__construct') {
                foreach ($stmt->stmts ?? [] as $statement) {
                    if ($statement instanceof Node\Stmt\Expression) {
                        $statement = $statement->expr;
                    }
                    if ($statement instanceof Node\Expr\MethodCall
                        && $statement->name instanceof Node\Identifier
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

    private function isYamlDiscovery(Node\Stmt\Class_ $class): bool
    {
        foreach ($class->stmts as $stmt) {
            // YAML discovery plugin managers must override getDiscovery.
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === 'getDiscovery') {
                foreach ($stmt->stmts ?? [] as $methodStmt) {
                    if ($methodStmt instanceof Node\Stmt\If_) {
                        foreach ($methodStmt->stmts as $ifStmt) {
                            if ($ifStmt instanceof Node\Stmt\Expression) {
                                $ifStmtExpr = $ifStmt->expr;
                                if ($ifStmtExpr instanceof Node\Expr\Assign) {
                                    $ifStmtExprVar = $ifStmtExpr->var;
                                    if ($ifStmtExprVar instanceof Node\Expr\PropertyFetch
                                        && $ifStmtExprVar->var instanceof Node\Expr\Variable
                                        && $ifStmtExprVar->name instanceof Node\Identifier
                                        && $ifStmtExprVar->name->name === 'discovery'
                                    ) {
                                        $ifStmtExprExpr = $ifStmtExpr->expr;
                                        if ($ifStmtExprExpr instanceof Node\Expr\New_
                                            && ($ifStmtExprExpr->class instanceof Node\Name)
                                            && $ifStmtExprExpr->class->toString() === 'Drupal\Core\Plugin\Discovery\YamlDiscovery') {
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

    private function inspectYamlPluginManager(Node\Stmt\Class_ $class): array
    {
        $errors = [];

        $fqn = $class->namespacedName;
        $reflection = new \ReflectionClass($fqn);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            throw new ShouldNotHappenException();
        }

        if ($constructor->class !== $fqn->toString()) {
            $errors[] = sprintf('%s must override __construct if using YAML plugins.', $fqn);
        } else {
            foreach ($class->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === '__construct') {
                    foreach ($stmt->stmts ?? [] as $constructorStmt) {
                        if ($constructorStmt instanceof Node\Stmt\Expression) {
                            $constructorStmt = $constructorStmt->expr;
                        }
                        if ($constructorStmt instanceof Node\Expr\StaticCall
                            && $constructorStmt->class instanceof Node\Name
                            && ((string)$constructorStmt->class === 'parent')
                            && $constructorStmt->name instanceof Node\Identifier
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
