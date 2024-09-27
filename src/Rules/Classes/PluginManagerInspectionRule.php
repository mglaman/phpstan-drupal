<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Classes;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use function sprintf;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Class_>
 */
class PluginManagerInspectionRule implements Rule
{
    /** @var ReflectionProvider */
    private $reflectionProvider;
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Node\Stmt\Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->namespacedName === null) {
            // anonymous class
            return [];
        }
        if ($node->extends === null) {
            return [];
        }
        if (str_contains($node->namespacedName->toLowerString(), 'test')) {
            return [];
        }

        $pluginManagerType = $scope->resolveTypeByName($node->namespacedName);
        $pluginManagerInterfaceType = new ObjectType(PluginManagerInterface::class);
        if (!$pluginManagerInterfaceType->isSuperTypeOf($pluginManagerType)->yes()) {
            return [];
        }
        $defaultPluginManager = new ObjectType(DefaultPluginManager::class);
        if ($defaultPluginManager->equals($pluginManagerType)) {
            return [];
        }

        $constructorMethodNode = (new NodeFinder())->findFirst($node->stmts, static function (Node $node) {
            return $node instanceof Node\Stmt\ClassMethod && $node->name->toString() === '__construct';
        });
        if (!$constructorMethodNode instanceof Node\Stmt\ClassMethod) {
            return [];
        }

        $errors = [];
        if ($this->isYamlDiscovery($node)) {
            $errors = $this->inspectYamlPluginManager($node, $constructorMethodNode);
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
                ->identifier('plugin.manager.alterInfoMissing')
                ->tip('For example, to invoke hook_mymodule_data_alter() call alterInfo with "mymodule_data".')
                ->line($node->getStartLine())
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

    private function inspectYamlPluginManager(Node\Stmt\Class_ $class, Node\Stmt\ClassMethod $constructorMethodNode): array
    {
        $errors = [];

        $fqn = (string) $class->namespacedName;
        $reflection = $this->reflectionProvider->getClass($fqn);
        $constructor = $reflection->getConstructor();

        if ($constructor->getDeclaringClass()->getName() !== $fqn) {
            $errors[] = sprintf('%s must override __construct if using YAML plugins.', $fqn);
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
                    $errors[] = 'YAML plugin managers should not invoke its parent constructor.';
                }
            }
        }
        return $errors;
    }
}
