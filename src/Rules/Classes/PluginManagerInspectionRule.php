<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Classes;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function str_contains;
use function strtolower;

/**
 * @implements Rule<InClassNode>
 */
final class PluginManagerInspectionRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        if ($classReflection->isAnonymous()) {
            return [];
        }
        $originalNode = $node->getOriginalNode();
        if (!$originalNode instanceof Node\Stmt\Class_) {
            return [];
        }
        if ($originalNode->extends === null) {
            return [];
        }
        if (str_contains(strtolower($classReflection->getName()), 'test')) {
            return [];
        }

        if (!$classReflection->is(PluginManagerInterface::class)) {
            return [];
        }
        if ($classReflection->getName() === DefaultPluginManager::class) {
            return [];
        }

        // Only look at the class's own methods. A recursive search would also
        // match a constructor declared by an anonymous class nested in a method.
        $constructorMethodNode = $originalNode->getMethod('__construct');
        if ($constructorMethodNode === null) {
            return [];
        }

        $errors = [];
        if ($this->isYamlDiscovery($originalNode)) {
            $errors = $this->inspectYamlPluginManager($classReflection, $constructorMethodNode);
        } else {
            // @todo inspect annotated plugin managers.
        }

        $alterInfoMethodNode = (new NodeFinder())->findFirst($constructorMethodNode->stmts ?? [], static function (Node $node) {
            return $node instanceof Node\Stmt\Expression
                && $node->expr instanceof Node\Expr\MethodCall
                && $node->expr->name instanceof Node\Identifier
                && $node->expr->name->toString() === 'alterInfo';
        });

        if ($alterInfoMethodNode === null) {
            $errors[] = RuleErrorBuilder::message(
                'Plugin managers should call alterInfo to allow plugin definitions to be altered.'
            )
                ->tip('For example, to invoke hook_mymodule_data_alter() call alterInfo with "mymodule_data".')
                ->identifier('pluginManagerInspection.alterInfoMissing')
                ->build();
        }

        return $errors;
    }

    private function isYamlDiscovery(Node\Stmt\Class_ $class): bool
    {
        $nodeFinder = new NodeFinder();
        $getDiscoveryMethodNode = $nodeFinder->findFirst($class->stmts, static function (Node $node) {
            return $node instanceof Node\Stmt\ClassMethod && $node->name->toString() === 'getDiscovery';
        });
        if (!$getDiscoveryMethodNode instanceof Node\Stmt\ClassMethod) {
            return false;
        }

        $assignDiscovery = $nodeFinder->findFirstInstanceOf($getDiscoveryMethodNode->stmts ?? [], Node\Expr\Assign::class);
        if ($assignDiscovery === null) {
            return false;
        }
        if ($assignDiscovery->expr instanceof Node\Expr\New_
            && $assignDiscovery->expr->class instanceof Node\Name
            && $assignDiscovery->expr->class->toString() === 'Drupal\Core\Plugin\Discovery\YamlDiscovery') {
            return true;
        }

        return false;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function inspectYamlPluginManager(ClassReflection $classReflection, Node\Stmt\ClassMethod $constructorMethodNode): array
    {
        $errors = [];

        $fqn = $classReflection->getName();
        if (!$classReflection->hasConstructor()) {
            return $errors;
        }
        $constructor = $classReflection->getConstructor();

        if ($constructor->getDeclaringClass()->getName() !== $fqn) {
            $errors[] = RuleErrorBuilder::message(
                sprintf('%s must override __construct if using YAML plugins.', $fqn)
            )
                ->identifier('pluginManagerInspection.constructorOverrideMissing')
                ->build();
        } else {
            foreach ($constructorMethodNode->stmts ?? [] as $constructorStmt) {
                if ($constructorStmt instanceof Node\Stmt\Expression) {
                    $constructorStmt = $constructorStmt->expr;
                }
                if ($constructorStmt instanceof Node\Expr\StaticCall
                    && $constructorStmt->class instanceof Node\Name
                    && ((string)$constructorStmt->class === 'parent')
                    && $constructorStmt->name instanceof Node\Identifier
                    && $constructorStmt->name->name === '__construct') {
                    $errors[] = RuleErrorBuilder::message(
                        'YAML plugin managers should not invoke its parent constructor.'
                    )
                        ->identifier('pluginManagerInspection.yamlPluginManagersInvokesParentConstructor')
                        ->build();
                }
            }
        }
        return $errors;
    }
}
