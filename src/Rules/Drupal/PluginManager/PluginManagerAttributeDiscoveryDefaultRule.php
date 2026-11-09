<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\PluginManager;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;

/**
 * Detects plugin managers whose attribute argument comes from a default value.
 *
 * Complements PluginManagerAttributeDiscoveryRule for two patterns where the
 * value passed to DefaultPluginManager::__construct() is not inferable as a
 * constant string, but is declared with a constant string default:
 *
 * - a static property: `parent::__construct(..., static::$annotation)`
 * - a constructor parameter: `__construct(..., $annotation = 'Foo\Annotation')`
 *   forwarded to `parent::__construct()`
 *
 * These are reported with a distinct identifier and message because the default
 * is not necessarily the final value: a subclass may override the static
 * property, and a caller may pass another value for the parameter. The rule is
 * conservative otherwise: the default must be a string naming a resolvable
 * class that is not an attribute class, and the property or parameter must not
 * be assigned anywhere in the class being analysed.
 *
 * @see https://www.drupal.org/node/3522776
 * @see https://www.drupal.org/node/3395582
 *
 * @extends AbstractPluginManagerAttributeDiscoveryRule<InClassNode>
 */
final class PluginManagerAttributeDiscoveryDefaultRule extends AbstractPluginManagerAttributeDiscoveryRule
{
    private const TIP = 'Provide an Attribute class and keep the Annotation class for BC. See https://www.drupal.org/node/3522776 and https://www.drupal.org/node/3395582';

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        if (!$this->callsDefaultPluginManagerConstructor($classReflection)) {
            return [];
        }

        $classNode = $node->getOriginalNode();
        // Own methods only, so constructors of anonymous classes are never found.
        $constructor = $classNode->getMethod('__construct');
        if ($constructor === null) {
            return [];
        }

        $errors = [];
        /** @var list<Node\Expr\StaticCall> $calls */
        $calls = $this->findInScope(
            (array) $constructor->stmts,
            fn (Node $n): bool => $n instanceof Node\Expr\StaticCall && $this->isParentConstructorCall($n)
        );
        foreach ($calls as $call) {
            $argument = $this->findAttributeArgument($call);
            if ($argument === null) {
                continue;
            }
            $value = $argument->value;
            $error = null;
            if ($value instanceof Node\Expr\StaticPropertyFetch) {
                $error = $this->checkStaticProperty($value, $classReflection, $classNode);
            } elseif ($value instanceof Node\Expr\Variable) {
                $error = $this->checkParameter($value, $classReflection, $constructor);
            }
            if ($error === null) {
                continue;
            }
            $errors[] = RuleErrorBuilder::message($error)
                ->identifier('pluginManager.attributeDiscoveryMissingDefault')
                ->line($argument->getStartLine())
                ->tip(self::TIP)
                ->build();
        }

        return $errors;
    }

    private function checkStaticProperty(Node\Expr\StaticPropertyFetch $fetch, ClassReflection $classReflection, Node\Stmt\ClassLike $classNode): ?string
    {
        if (!$fetch->name instanceof Node\VarLikeIdentifier || !$fetch->class instanceof Node\Name) {
            return null;
        }
        // Only the class being analysed: static:: (late static binding resolves
        // to it or a subclass), self:: or the class's own name.
        $className = $fetch->class->toLowerString();
        if (!in_array($className, ['static', 'self', strtolower($classReflection->getName())], true)) {
            return null;
        }
        $propertyName = $fetch->name->toString();
        if (!$classReflection->hasNativeProperty($propertyName)) {
            return null;
        }
        $property = $classReflection->getNativeProperty($propertyName);
        if (!$property->isStatic()) {
            return null;
        }

        // The property must not be written to anywhere in the analysed class.
        $writes = (new NodeFinder())->find(
            $classNode->stmts,
            static fn (Node $n): bool => self::isWriteTo($n, static fn (Node $target): bool => $target instanceof Node\Expr\StaticPropertyFetch
                && $target->name instanceof Node\VarLikeIdentifier
                && $target->name->toString() === $propertyName)
        );
        if ($writes !== []) {
            return null;
        }

        try {
            $default = $property->getNativeReflection()->getDefaultValue();
        } catch (Throwable $e) {
            return null;
        }
        if (!is_string($default)) {
            return null;
        }
        $attributeClass = $this->resolveNonAttributeClass($default);
        if ($attributeClass === null) {
            return null;
        }

        return sprintf(
            'Plugin manager %s passes the value of static property $%s, which defaults to %s, as the plugin definition attribute name to DefaultPluginManager::__construct(), but that is not an attribute class implementing %s. Unless a subclass overrides the property with an attribute class, not supporting attribute discovery is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0.',
            $classReflection->getName(),
            $propertyName,
            $attributeClass->getName(),
            self::ATTRIBUTE_INTERFACE
        );
    }

    private function checkParameter(Node\Expr\Variable $variable, ClassReflection $classReflection, Node\Stmt\ClassMethod $constructor): ?string
    {
        if (!is_string($variable->name)) {
            return null;
        }
        $name = $variable->name;

        $default = null;
        foreach ($constructor->params as $param) {
            if ($param->var instanceof Node\Expr\Variable && $param->var->name === $name) {
                if ($param->byRef || $param->variadic) {
                    return null;
                }
                $default = $param->default;
                break;
            }
        }
        if ($default === null) {
            return null;
        }
        if ($default instanceof Node\Scalar\String_) {
            $defaultClassName = $default->value;
        } elseif ($default instanceof Node\Expr\ClassConstFetch
            && $default->class instanceof Node\Name
            && $default->name instanceof Node\Identifier
            && $default->name->toLowerString() === 'class'
        ) {
            $resolved = $default->class->getAttribute('resolvedName');
            $defaultClassName = $resolved instanceof Node\Name ? $resolved->toString() : $default->class->toString();
        } else {
            return null;
        }

        // The parameter must not be reassigned in the constructor.
        $writes = $this->findInScope(
            (array) $constructor->stmts,
            static fn (Node $n): bool => self::isWriteTo($n, static fn (Node $target): bool => $target instanceof Node\Expr\Variable && $target->name === $name)
        );
        if ($writes !== []) {
            return null;
        }

        $attributeClass = $this->resolveNonAttributeClass($defaultClassName);
        if ($attributeClass === null) {
            return null;
        }

        return sprintf(
            'Plugin manager %s forwards constructor parameter $%s, which defaults to %s, as the plugin definition attribute name to DefaultPluginManager::__construct(), but that is not an attribute class implementing %s. Unless callers pass an attribute class, not supporting attribute discovery is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0.',
            $classReflection->getName(),
            $name,
            $attributeClass->getName(),
            self::ATTRIBUTE_INTERFACE
        );
    }

    /**
     * Finds matching nodes without leaving the current variable scope.
     *
     * Anonymous classes, nested functions, closures and arrow functions have
     * their own scope, so their bodies are skipped. A closure's `use` list is
     * still inspected because `use (&$x)` can write to the outer variable.
     *
     * @param array<Node> $nodes
     * @param callable(Node): bool $matches
     * @return list<Node>
     */
    private function findInScope(array $nodes, callable $matches): array
    {
        $visitor = new class ($matches) extends NodeVisitorAbstract {

            /** @var list<Node> */
            public array $found = [];

            /** @var callable(Node): bool */
            private $matches;

            /** @param callable(Node): bool $matches */
            public function __construct(callable $matches)
            {
                $this->matches = $matches;
            }

            public function enterNode(Node $node): ?int
            {
                if (($this->matches)($node)) {
                    $this->found[] = $node;
                }
                if ($node instanceof Node\Expr\Closure) {
                    foreach ($node->uses as $use) {
                        if (($this->matches)($use)) {
                            $this->found[] = $use;
                        }
                    }
                }
                if ($node instanceof Node\Stmt\ClassLike
                    || $node instanceof Node\FunctionLike
                ) {
                    return NodeVisitor::DONT_TRAVERSE_CHILDREN;
                }
                return null;
            }

        };
        $traverser = new NodeTraverser($visitor);
        $traverser->traverse($nodes);

        return $visitor->found;
    }

    /**
     * Whether the node writes to (or takes a reference of) a matching target.
     *
     * @param callable(Node): bool $matches
     */
    private static function isWriteTo(Node $node, callable $matches): bool
    {
        $targets = [];
        if ($node instanceof Node\Expr\Assign || $node instanceof Node\Expr\AssignRef || $node instanceof Node\Expr\AssignOp) {
            $targets[] = $node->var;
            if ($node instanceof Node\Expr\AssignRef) {
                $targets[] = $node->expr;
            }
        } elseif ($node instanceof Node\Stmt\Foreach_) {
            $targets[] = $node->valueVar;
            if ($node->keyVar !== null) {
                $targets[] = $node->keyVar;
            }
        } elseif ($node instanceof Node\Stmt\Unset_) {
            $targets = $node->vars;
        } elseif ($node instanceof Node\Stmt\Global_) {
            $targets = $node->vars;
        } elseif ($node instanceof Node\Stmt\StaticVar) {
            $targets[] = $node->var;
        } elseif ($node instanceof Node\ClosureUse && $node->byRef) {
            $targets[] = $node->var;
        } elseif ($node instanceof Node\Expr\PreInc || $node instanceof Node\Expr\PostInc || $node instanceof Node\Expr\PreDec || $node instanceof Node\Expr\PostDec) {
            $targets[] = $node->var;
        }
        foreach ($targets as $target) {
            $found = (new NodeFinder())->findFirst($target, $matches);
            if ($found !== null) {
                return true;
            }
        }
        return false;
    }
}
