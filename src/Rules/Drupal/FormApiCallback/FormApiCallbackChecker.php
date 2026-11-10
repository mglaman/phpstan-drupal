<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\FormApiCallback;

use Drupal;
use mglaman\PHPStanDrupal\Drupal\ServiceMap;
use mglaman\PHPStanDrupal\Drupal\ServiceResultCacheValueExtension;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use function array_merge;
use function count;
use function explode;
use function implode;
use function ltrim;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function substr;
use function substr_count;
use function ucfirst;
use function version_compare;

/**
 * Checks Form API callbacks that fail when Drupal calls them.
 *
 * Drupal 11.3 and later pass form callbacks to
 * CallableResolver::getCallableFromDefinition(), which also accepts
 * 'Class::method' strings for non-static methods and 'service:method' strings.
 * Earlier versions only accept native PHP callables.
 *
 * @see \Drupal\Core\Form\FormState::prepareCallback()
 * @see \Drupal\Core\Utility\CallableResolver::getCallableFromDefinition()
 */
final class FormApiCallbackChecker
{

    /**
     * Keys that hold a list of callbacks.
     *
     * #value_callback is left out: FormBuilder::handleInputElement() falls back
     * to FormElementBase::valueCallback() when it is not callable, so it never
     * fails.
     */
    public const CALLBACK_LIST_KEYS = [
        '#validate',
        '#submit',
        '#element_validate',
        '#process',
        '#after_build',
    ];

    public const AJAX_KEY = '#ajax';

    public const AJAX_CALLBACK_KEY = 'callback';

    private const FORM_INTERFACE = 'Drupal\Core\Form\FormInterface';

    private const HOOK_ATTRIBUTE = 'Drupal\Core\Hook\Attribute\Hook';

    private const FUNCTION_NAME_PATTERN = '#^\\\\?[a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*(\\\\[a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*)*\\z#';

    private const CLASS_METHOD_PATTERN = '#^([a-zA-Z_\\x80-\\xff\\\\][a-zA-Z0-9_\\x80-\\xff\\\\]*)::([a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*)\\z#';

    private ReflectionProvider $reflectionProvider;

    private ServiceMap $serviceMap;

    private ExprPrinter $exprPrinter;

    private bool $resolvesCallableDefinitions;

    public function __construct(
        ReflectionProvider $reflectionProvider,
        ServiceMap $serviceMap,
        ExprPrinter $exprPrinter
    ) {
        $this->reflectionProvider = $reflectionProvider;
        $this->serviceMap = $serviceMap;
        $this->exprPrinter = $exprPrinter;
        $this->resolvesCallableDefinitions = version_compare(Drupal::VERSION, '11.2.99', '>');
    }

    /**
     * @param Scope&DependencyTracker $scope
     * @return list<IdentifierRuleError>
     */
    public function checkCallbackList(string $key, Array_ $callbacks, Scope $scope): array
    {
        $errors = [];
        foreach ($callbacks->items as $item) {
            if ($item->unpack) {
                continue;
            }
            $errors[] = $this->checkCallback($key, $item->value, $scope);
        }
        return array_merge(...$errors);
    }

    /**
     * @param Scope&DependencyTracker $scope
     * @return list<IdentifierRuleError>
     */
    public function checkAjaxSettings(Array_ $settings, Scope $scope): array
    {
        foreach ($settings->items as $item) {
            if ($item->key instanceof String_ && $item->key->value === self::AJAX_CALLBACK_KEY) {
                return $this->checkCallback(self::AJAX_KEY, $item->value, $scope);
            }
        }
        return [];
    }

    /**
     * @param Scope&DependencyTracker $scope
     * @return list<IdentifierRuleError>
     */
    public function checkCallback(string $key, Expr $callback, Scope $scope): array
    {
        if ($callback instanceof Concat) {
            $lateStaticCallback = $this->findLateStaticStringCallback($callback, $scope);
            if ($lateStaticCallback !== null) {
                [$classReflection, $methodName, $usesServiceNotation] = $lateStaticCallback;
                if ($usesServiceNotation && !$this->resolvesCallableDefinitions) {
                    return [$this->buildServiceNotationError($key, $callback)];
                }
                return $this->checkClassMethodString($key, $callback, $classReflection, $methodName, $this->isAbstractOrInterface($classReflection), $scope);
            }
        }

        $type = $scope->getType($callback);
        $constantStrings = $type->getConstantStrings();
        if (count($constantStrings) === 1) {
            return $this->checkString($key, $callback, $constantStrings[0]->getValue(), $scope);
        }
        $constantArrays = $type->getConstantArrays();
        if (count($constantArrays) === 1) {
            return $this->checkArray($key, $callback, $constantArrays[0], $scope);
        }
        return [];
    }

    /**
     * Finds the class and method of static::class . '::method' or ':method'.
     *
     * @return array{ClassReflection, string, bool}|null
     *   The class, the method name, and whether the callback uses the
     *   service:method notation.
     */
    private function findLateStaticStringCallback(Concat $callback, Scope $scope): ?array
    {
        $classType = $scope->getType($callback->left);
        $methodStrings = $scope->getType($callback->right)->getConstantStrings();
        if (count($methodStrings) !== 1 || !$classType->isClassString()->yes()) {
            return null;
        }
        $objectType = $classType->getClassStringObjectType();
        $classReflections = $objectType->getObjectClassReflections();
        if (!$objectType instanceof StaticType || count($classReflections) !== 1) {
            return null;
        }
        $methodString = $methodStrings[0]->getValue();
        if (str_starts_with($methodString, '::')) {
            return [$classReflections[0], substr($methodString, 2), false];
        }
        if (str_starts_with($methodString, ':') && !str_contains(substr($methodString, 1), ':')) {
            return [$classReflections[0], substr($methodString, 1), true];
        }
        return null;
    }

    /**
     * @param Scope&DependencyTracker $scope
     * @return list<IdentifierRuleError>
     */
    private function checkString(string $key, Expr $callback, string $value, Scope $scope): array
    {
        if (str_starts_with($value, '::')) {
            $formClass = $this->findKnownFormClass($scope);
            if ($formClass === null) {
                return [];
            }
            return $this->checkMethodIsCallable($key, $callback, $formClass, substr($value, 2), false, false);
        }
        if (preg_match(self::CLASS_METHOD_PATTERN, $value, $matches) === 1) {
            if (!$this->reflectionProvider->hasClass($matches[1])) {
                return [];
            }
            return $this->checkClassMethodString($key, $callback, $this->reflectionProvider->getClass($matches[1]), $matches[2], false, $scope);
        }
        if (substr_count($value, ':') === 1) {
            return $this->checkServiceMethodString($key, $callback, $value, $scope);
        }
        if (str_contains($value, ':')) {
            return [];
        }
        return $this->checkFunctionString($key, $callback, $value, $scope);
    }

    /**
     * Checks 'Class::method' strings.
     *
     * Before Drupal 11.3, PHP calls the method statically. Drupal 11.3 and later
     * create an object of the class and call the method on it.
     *
     * @return list<IdentifierRuleError>
     */
    private function checkClassMethodString(
        string $key,
        Expr $callback,
        ClassReflection $classReflection,
        string $methodName,
        bool $methodMayBeInSubclass,
        Scope $scope
    ): array {
        $calledStatically = !$this->resolvesCallableDefinitions;
        $errors = $this->checkMethodIsCallable($key, $callback, $classReflection, $methodName, $calledStatically, $methodMayBeInSubclass);
        if (count($errors) > 0 || !$calledStatically) {
            return $errors;
        }
        return $this->checkMethodIsStatic(
            $key,
            $callback,
            $classReflection,
            $methodName,
            $scope,
            sprintf("Drupal 11.3 and later accept '%s::%s' for non-static methods.", $classReflection->getName(), $methodName)
        );
    }

    /**
     * @param Scope&DependencyTracker $scope
     * @return list<IdentifierRuleError>
     */
    private function checkServiceMethodString(string $key, Expr $callback, string $value, Scope $scope): array
    {
        if (!$this->resolvesCallableDefinitions) {
            return [$this->buildServiceNotationError($key, $callback)];
        }
        [$classOrServiceId, $methodName] = explode(':', $value, 2);
        $definition = $this->resolveClassResolverDefinition($classOrServiceId, $scope);
        if ($definition === null) {
            return $this->isPrivateService($classOrServiceId) ? [$this->buildPrivateServiceError($key, $callback, $classOrServiceId)] : [];
        }
        [$classReflection, $isServiceClass] = $definition;
        if ($this->isAbstractOrInterface($classReflection)) {
            return $isServiceClass ? [] : [$this->buildNotInstantiableError($key, $callback, $classReflection)];
        }
        return $this->checkMethodIsCallable($key, $callback, $classReflection, $methodName, false, false);
    }

    /**
     * @param Scope&DependencyTracker $scope
     * @return list<IdentifierRuleError>
     */
    private function checkFunctionString(string $key, Expr $callback, string $value, Scope $scope): array
    {
        $isFunctionName = preg_match(self::FUNCTION_NAME_PATTERN, $value) === 1;
        if ($isFunctionName && $this->reflectionProvider->hasFunction(new FullyQualified(ltrim($value, '\\')), null)) {
            return [];
        }
        if ($this->resolvesCallableDefinitions) {
            // CallableResolver also accepts invokable services and classes.
            $definition = $this->resolveClassResolverDefinition($value, $scope);
            if ($definition !== null) {
                return $this->checkClassIsInvokable($key, $callback, ...$definition);
            }
            if ($this->isPrivateService($value)) {
                return [$this->buildPrivateServiceError($key, $callback, $value)];
            }
            if (!$isFunctionName) {
                return [];
            }
        }
        return [
            $this->buildError(
                $key,
                $callback,
                sprintf('refers to function %s(), which does not exist.', $value),
                'formApiCallback.notFound'
            ),
        ];
    }

    /**
     * Reports a class or service without a public __invoke() method.
     *
     * CallableResolver throws when the object it creates is not callable.
     *
     * @return list<IdentifierRuleError>
     */
    private function checkClassIsInvokable(string $key, Expr $callback, ClassReflection $classReflection, bool $isServiceClass): array
    {
        if ($this->isAbstractOrInterface($classReflection)) {
            return $isServiceClass ? [] : [$this->buildNotInstantiableError($key, $callback, $classReflection)];
        }
        if ($classReflection->hasNativeMethod('__invoke')
            && $classReflection->getNativeMethod('__invoke')->isPublic()
        ) {
            return [];
        }
        return [
            $this->buildError(
                $key,
                $callback,
                sprintf('refers to %s, which has no public __invoke() method.', $classReflection->getName()),
                'formApiCallback.notFound',
                "Add a public __invoke() method, or name the method: 'Class::method' or 'service:method'."
            ),
        ];
    }

    /**
     * Resolves a definition the way ClassResolver does.
     *
     * ClassResolver returns the service when the container has one, and
     * otherwise creates an object of the class. The container only has public
     * services. A service's object may be a subclass of its declared class, so
     * the second value says whether the class came from a service.
     *
     * @param Scope&DependencyTracker $scope
     * @return array{ClassReflection, bool}|null
     *   NULL when the definition can't be resolved, or the service is
     *   decorated: the container returns the outermost decorator.
     */
    private function resolveClassResolverDefinition(string $definition, Scope $scope): ?array
    {
        $scope->trackValueDependency(ServiceResultCacheValueExtension::class, $definition);
        $service = $this->serviceMap->getService($definition);
        if ($service !== null && $service->isPublic()) {
            $serviceClass = $service->getClass();
            if (count($service->getDecorators()) > 0
                || $serviceClass === null
                || !$this->reflectionProvider->hasClass($serviceClass)
            ) {
                return null;
            }
            return [$this->reflectionProvider->getClass($serviceClass), true];
        }
        if ($this->reflectionProvider->hasClass($definition)) {
            return [$this->reflectionProvider->getClass($definition), false];
        }
        return null;
    }

    private function isPrivateService(string $definition): bool
    {
        $service = $this->serviceMap->getService($definition);
        return $service !== null && !$service->isPublic();
    }

    private function buildPrivateServiceError(string $key, Expr $callback, string $serviceId): IdentifierRuleError
    {
        return $this->buildError(
            $key,
            $callback,
            sprintf("refers to service %s, which is not public. Drupal's container only returns public services.", $serviceId),
            'formApiCallback.privateService',
            'Make the service public, or use a class name.'
        );
    }

    private function buildNotInstantiableError(string $key, Expr $callback, ClassReflection $classReflection): IdentifierRuleError
    {
        return $this->buildError(
            $key,
            $callback,
            sprintf('refers to %s, which is %s, so Drupal cannot create an object of it.', $classReflection->getName(), $classReflection->isInterface() ? 'an interface' : 'abstract'),
            'formApiCallback.notInstantiable',
            'Use a concrete class.'
        );
    }

    private function buildServiceNotationError(string $key, Expr $callback): IdentifierRuleError
    {
        return $this->buildError(
            $key,
            $callback,
            'uses the service:method notation, which Drupal supports for form callbacks in 11.3 and later.',
            'formApiCallback.serviceNotation',
            "Use a static method, or '::method' in a form class."
        );
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkArray(string $key, Expr $callback, ConstantArrayType $callbackType, Scope $scope): array
    {
        $values = $callbackType->getValueTypes();
        if (count($values) !== 2 || count($callbackType->getOptionalKeys()) > 0) {
            return [];
        }
        [$target, $method] = $values;
        $methodNames = $method->getConstantStrings();
        if (count($methodNames) !== 1) {
            return [];
        }
        $methodName = $methodNames[0]->getValue();

        if ($target->isClassString()->yes()) {
            return $this->checkClassStringTarget($key, $callback, $target, $methodName, $scope);
        }
        $classReflections = $target->getObjectClassReflections();
        if (count($classReflections) !== 1 || !$target->isObject()->yes()) {
            return [];
        }
        $classReflection = $classReflections[0];
        return $this->checkMethodIsCallable($key, $callback, $classReflection, $methodName, false, $this->isAbstractOrInterface($classReflection));
    }

    /**
     * Checks [Foo::class, 'method'] and [static::class, 'method'].
     *
     * @return list<IdentifierRuleError>
     */
    private function checkClassStringTarget(string $key, Expr $callback, Type $target, string $methodName, Scope $scope): array
    {
        $classReflections = $target->getClassStringObjectType()->getObjectClassReflections();
        if (count($classReflections) !== 1) {
            return [];
        }
        $classReflection = $classReflections[0];
        // A literal class name is the exact class. static::class and
        // class-string<Foo> may be a subclass.
        $isExactClass = count($target->getConstantStrings()) === 1;
        $methodMayBeInSubclass = !$isExactClass && $this->isAbstractOrInterface($classReflection);
        $errors = $this->checkMethodIsCallable($key, $callback, $classReflection, $methodName, true, $methodMayBeInSubclass);
        if (count($errors) > 0) {
            return $errors;
        }
        $versionNote = $this->resolvesCallableDefinitions
            ? sprintf("The string '%s::%s' also works: Drupal creates the object.", $classReflection->getName(), $methodName)
            : '';
        return $this->checkMethodIsStatic($key, $callback, $classReflection, $methodName, $scope, $versionNote);
    }

    /**
     * Reports a method that does not exist or is not public.
     *
     * @return list<IdentifierRuleError>
     */
    private function checkMethodIsCallable(
        string $key,
        Expr $callback,
        ClassReflection $classReflection,
        string $methodName,
        bool $calledStatically,
        bool $methodMayBeInSubclass
    ): array {
        if ($methodMayBeInSubclass || $classReflection->hasNativeMethod($calledStatically ? '__callStatic' : '__call')) {
            return [];
        }
        if (!$classReflection->hasNativeMethod($methodName)) {
            if ($classReflection->hasMethod($methodName)) {
                return [];
            }
            return [
                $this->buildError(
                    $key,
                    $callback,
                    sprintf('refers to %s::%s(), which does not exist.', $classReflection->getName(), $methodName),
                    'formApiCallback.notFound'
                ),
            ];
        }
        if (!$classReflection->getNativeMethod($methodName)->isPublic()) {
            return [
                $this->buildError(
                    $key,
                    $callback,
                    sprintf('refers to %s::%s(), which is not public.', $classReflection->getName(), $methodName),
                    'formApiCallback.nonPublicMethod',
                    'Make the method public. Drupal calls form callbacks from outside the class.'
                ),
            ];
        }
        return [];
    }

    /**
     * Reports a static call to a method that exists but is not static.
     *
     * @return list<IdentifierRuleError>
     */
    private function checkMethodIsStatic(
        string $key,
        Expr $callback,
        ClassReflection $classReflection,
        string $methodName,
        Scope $scope,
        string $versionNote
    ): array {
        if (!$classReflection->hasNativeMethod($methodName)) {
            return [];
        }
        $method = $classReflection->getNativeMethod($methodName);
        if ($method->isStatic()) {
            return [];
        }

        $fixes = [];
        $formClass = $this->findKnownFormClass($scope);
        if ($formClass !== null && $formClass->is($classReflection->getName())) {
            $fixes[] = sprintf("use '::%s' to call it on the form object", $method->getName());
        }
        $fixes[] = 'make the method static';
        $tip = ucfirst(implode(', or ', $fixes)) . '.';
        if ($versionNote !== '') {
            $tip .= ' ' . $versionNote;
        }

        return [
            $this->buildError(
                $key,
                $callback,
                sprintf('calls non-static method %s::%s() statically.', $classReflection->getName(), $method->getName()),
                'formApiCallback.nonStaticMethod',
                $tip
            ),
        ];
    }

    /**
     * Returns the class of the form object '::method' callbacks run on.
     *
     * Returns NULL outside a concrete form class, in static methods, and in hook
     * implementations. A static method, such as a #process callback, has no
     * form object of its own, and an alter hook's '::method' refers to the
     * altered form's object.
     */
    private function findKnownFormClass(Scope $scope): ?ClassReflection
    {
        if (!$scope->isInClass()) {
            return null;
        }
        $classReflection = $scope->getClassReflection();
        if ($classReflection->isAbstract() || !$classReflection->implementsInterface(self::FORM_INTERFACE)) {
            return null;
        }
        $function = $scope->getFunction();
        if ($function instanceof ExtendedMethodReflection
            && ($function->isStatic() || $this->isHookImplementation($classReflection, $function))
        ) {
            return null;
        }
        return $classReflection;
    }

    /**
     * Whether the method, or its class, has the Hook attribute.
     *
     * A Hook attribute on the class can name any of its methods with the
     * method argument, so a class-level Hook marks every method as a possible
     * hook implementation.
     *
     * Attribute names come from native reflection. PHPStan's attribute
     * reflection drops an attribute with arguments when its class does not
     * exist. Drupal 10 has no Hook attribute class, but modules supporting
     * Drupal 10 and 11 still use #[Hook('...')].
     */
    private function isHookImplementation(ClassReflection $classReflection, ExtendedMethodReflection $method): bool
    {
        $nativeClass = $classReflection->getNativeReflection();
        foreach ($nativeClass->getAttributes() as $attribute) {
            if ($this->isHookAttribute($attribute->getName())) {
                return true;
            }
        }
        if (!$nativeClass->hasMethod($method->getName())) {
            return false;
        }
        foreach ($nativeClass->getMethod($method->getName())->getAttributes() as $attribute) {
            if ($this->isHookAttribute($attribute->getName())) {
                return true;
            }
        }
        return false;
    }

    private function isHookAttribute(string $attributeName): bool
    {
        if ($attributeName === self::HOOK_ATTRIBUTE) {
            return true;
        }
        return $this->reflectionProvider->hasClass($attributeName)
            && $this->reflectionProvider->getClass($attributeName)->is(self::HOOK_ATTRIBUTE);
    }

    private function isAbstractOrInterface(ClassReflection $classReflection): bool
    {
        return $classReflection->isAbstract() || $classReflection->isInterface();
    }

    private function buildError(string $key, Expr $callback, string $problem, string $identifier, ?string $tip = null): IdentifierRuleError
    {
        $builder = RuleErrorBuilder::message(sprintf('%s callback %s %s', $key, $this->exprPrinter->printExpr($callback), $problem))
            ->line($callback->getStartLine())
            ->identifier($identifier);
        if ($tip !== null) {
            $builder->tip($tip);
        }
        return $builder->build();
    }
}
