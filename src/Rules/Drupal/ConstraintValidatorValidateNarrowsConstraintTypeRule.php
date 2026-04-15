<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use function array_key_exists;
use function count;
use function str_ends_with;
use function strrpos;
use function substr;

/**
 * Requires ConstraintValidator::validate() to narrow the $constraint parameter type.
 *
 * PHPStan cannot infer that `$constraint` is a specific subclass of Constraint
 * inside a validator's validate() method. Drupal core's convention is to use
 * `assert($constraint instanceof SpecificConstraint)` to narrow the type, which
 * PHPStan handles natively.
 *
 * This rule fires when a class following the FooValidator/Foo naming convention
 * does not assert the constraint type in its validate() method.
 *
 * @implements Rule<Node\Stmt\ClassMethod>
 */
final class ConstraintValidatorValidateNarrowsConstraintTypeRule implements Rule
{

    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Node\Stmt\ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name->toString() !== 'validate') {
            return [];
        }
        if (!$scope->isInClass()) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if (!$classReflection->isSubclassOfClass($this->reflectionProvider->getClass(ConstraintValidator::class))) {
            return [];
        }

        // Derive the matching constraint class name by stripping 'Validator' suffix.
        $fqcn = $classReflection->getName();
        if (!str_ends_with($fqcn, 'Validator')) {
            return [];
        }
        $constraintClass = substr($fqcn, 0, -9);

        if (!$this->reflectionProvider->hasClass($constraintClass)) {
            return [];
        }
        $constraintReflection = $this->reflectionProvider->getClass($constraintClass);
        if (!$constraintReflection->isSubclassOfClass($this->reflectionProvider->getClass(Constraint::class))) {
            return [];
        }

        // Find the $constraint parameter name (second param by Symfony convention).
        $constraintParamName = 'constraint';
        if (array_key_exists(1, $node->params)) {
            $param = $node->params[1];
            if ($param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
                $constraintParamName = $param->var->name;
            }
        }

        // Check if the method body asserts the concrete constraint type.
        if ($this->hasConstraintAssertion($node->stmts ?? [], $constraintParamName)) {
            return [];
        }

        $validatorFqcn = $classReflection->getName();
        $validatorPos = strrpos($validatorFqcn, '\\');
        $validatorShortName = $validatorPos !== false ? substr($validatorFqcn, $validatorPos + 1) : $validatorFqcn;
        $constraintFqcn = $constraintReflection->getName();
        $constraintPos = strrpos($constraintFqcn, '\\');
        $constraintShortName = $constraintPos !== false ? substr($constraintFqcn, $constraintPos + 1) : $constraintFqcn;

        return [
            RuleErrorBuilder::message(sprintf(
                'Method %s::validate() does not narrow the $%s parameter type. Add assert($%s instanceof %s) to allow PHPStan to infer constraint-specific properties.',
                $validatorShortName,
                $constraintParamName,
                $constraintParamName,
                $constraintShortName
            ))
            ->identifier('drupal.constraintValidator.missingNarrow')
            ->tip('See https://www.drupal.org/project/drupal/issues/3246287')
            ->build(),
        ];
    }

    /**
     * Returns true if any statement in the list is assert($var instanceof SomeClass).
     *
     * @param Node\Stmt[] $stmts
     */
    private function hasConstraintAssertion(array $stmts, string $paramName): bool
    {
        foreach ($stmts as $stmt) {
            if (!$stmt instanceof Node\Stmt\Expression) {
                continue;
            }
            $expr = $stmt->expr;
            if (!$expr instanceof Node\Expr\FuncCall) {
                continue;
            }
            if (!$expr->name instanceof Node\Name || $expr->name->toString() !== 'assert') {
                continue;
            }
            $args = $expr->getArgs();
            if (count($args) === 0) {
                continue;
            }
            $assertArg = $args[0]->value;
            if (!$assertArg instanceof Node\Expr\Instanceof_) {
                continue;
            }
            if (!$assertArg->expr instanceof Node\Expr\Variable) {
                continue;
            }
            if ($assertArg->expr->name === $paramName) {
                return true;
            }
        }

        return false;
    }
}
