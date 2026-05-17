<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function class_exists;
use function count;
use function preg_match;

/**
 * Rule to validate form alter hook implementations using the Hook attribute.
 *
 * Validates that OOP hooks implementing form_alter, form_FORM_ID_alter, and
 * form_BASE_FORM_ID_alter have the correct method signatures.
 *
 * @implements Rule<ClassMethod>
 */
class HookFormAlterRule implements Rule
{

    /**
     * Pattern matching form alter hook names.
     */
    private const FORM_ALTER_PATTERNS = [
        '/^form_alter$/',
        '/^form_[a-zA-Z0-9_]+_alter$/',
    ];


    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // Skip if Hook attribute class doesn't exist (older Drupal versions)
        if (!class_exists(Hook::class)) {
            return [];
        }

        return $this->processMethod($node, $scope);
    }

    /**
     * Process a method with Hook attribute.
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processMethod(ClassMethod $method, Scope $scope): array
    {
        if (!$scope->isInClass()) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        $nativeClass = $classReflection->getNativeReflection();

        if (!$nativeClass->hasMethod($method->name->toString())) {
            return [];
        }

        $nativeMethod = $nativeClass->getMethod($method->name->toString());
        $hookAttributes = $nativeMethod->getAttributes(Hook::class);

        if (count($hookAttributes) === 0) {
            return [];
        }

        $errors = [];
        foreach ($hookAttributes as $hookAttribute) {
            /** @var Hook $hookInstance */
            $hookInstance = $hookAttribute->newInstance();
            if ($this->isFormAlterHook($hookInstance->hook)) {
                $methodErrors = $this->validateMethodSignature($method, $scope, $hookInstance->hook);
                $errors[] = $methodErrors;
            }
        }

        return array_merge(...$errors);
    }


    /**
     * Check if a hook name matches form alter patterns.
     */
    private function isFormAlterHook(string $hookName): bool
    {
        foreach (self::FORM_ALTER_PATTERNS as $pattern) {
            if (preg_match($pattern, $hookName) === 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate method signature for method-targeted hooks.
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function validateMethodSignature(ClassMethod $method, Scope $scope, string $hookName): array
    {
        $expectedSignature = 'Expected signature: method(&$form, \Drupal\Core\Form\FormStateInterface $form_state[, $form_id])';
        $paramCount = count($method->params);

        if ($paramCount < 2 || $paramCount > 3) {
            return [
                RuleErrorBuilder::message(
                    sprintf('Form alter hook "%s" implementation must have 2 or 3 parameters. %s', $hookName, $expectedSignature)
                )
                ->line($method->getStartLine())
                ->identifier('hookFormAlter.invalidParameterCount')
                ->build()
            ];
        }

        $errors = [];

        // Validate first parameter (form array by reference)
        $formParam = $method->params[0];
        if (!$formParam->byRef) {
            $errors[] = RuleErrorBuilder::message(
                sprintf('Form alter hook "%s" first parameter must be passed by reference (&$form). %s', $hookName, $expectedSignature)
            )
            ->line($method->getStartLine())
            ->identifier('hookFormAlter.formParameterNotByRef')
            ->build();
        }

        // Validate parameter types if type hints are present
        if ($formParam->type !== null) {
            $formParamType = $scope->getFunctionType($formParam->type, false, false);
            if (!$formParamType->isArray()->yes()) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf('Form alter hook "%s" first parameter should be an array. %s', $hookName, $expectedSignature)
                )
                ->line($method->getStartLine())
                ->identifier('hookFormAlter.invalidFormParameterType')
                ->build();
            }
        }

        // Validate second parameter (FormStateInterface)
        $formStateParam = $method->params[1];
        if ($formStateParam->type !== null) {
            $formStateType = $scope->getFunctionType($formStateParam->type, false, false);
            $expectedFormStateType = new ObjectType(FormStateInterface::class);
            if (!$expectedFormStateType->isSuperTypeOf($formStateType)->yes()) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Form alter hook "%s" second parameter should be \Drupal\Core\Form\FormStateInterface, %s given. %s',
                        $hookName,
                        $formStateType->describe(VerbosityLevel::typeOnly()),
                        $expectedSignature
                    )
                )
                ->line($method->getStartLine())
                ->identifier('hookFormAlter.invalidFormStateParameterType')
                ->build();
            }
        }

        // Validate third parameter (string form_id) - only if present
        if ($paramCount === 3) {
            $formIdParam = $method->params[2];
            if ($formIdParam->type !== null) {
                $formIdType = $scope->getFunctionType($formIdParam->type, false, false);
                if (!$formIdType->isString()->yes()) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf(
                            'Form alter hook "%s" third parameter should be string, %s given. %s',
                            $hookName,
                            $formIdType->describe(VerbosityLevel::typeOnly()),
                            $expectedSignature
                        )
                    )
                    ->line($method->getStartLine())
                    ->identifier('hookFormAlter.invalidFormIdParameterType')
                    ->build();
                }
            }
        }

        return $errors;
    }
}
