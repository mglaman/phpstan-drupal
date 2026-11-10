<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_diff;
use function array_keys;
use function array_map;
use function array_pop;
use function array_values;
use function count;
use function implode;
use function is_int;
use function is_string;
use function max;
use function sort;
use function sprintf;

/**
 * Reports #lazy_builder render arrays that break Renderer's rules.
 *
 * Renderer::doRender() throws when #create_placeholder is set without a
 * #lazy_builder callback. Its other #lazy_builder rules are assert() calls,
 * which only fail with assertions on. Production renders the wrong thing.
 *
 * Only array literals are checked. An array built across several statements
 * would need data-flow tracking.
 *
 * @see \Drupal\Core\Render\Renderer::doRender()
 *
 * @implements Rule<Array_>
 */
final class LazyBuilderRule implements Rule
{

    private const LAZY_BUILDER = '#lazy_builder';

    private const CREATE_PLACEHOLDER = '#create_placeholder';

    /**
     * Keys Renderer allows next to #lazy_builder.
     *
     * Renderer adds #weight and #printed itself, so it allows them too.
     */
    private const SUPPORTED_KEYS = [
        self::LAZY_BUILDER,
        '#cache',
        self::CREATE_PLACEHOLDER,
        '#lazy_builder_preview',
        '#placeholder_strategy_denylist',
        '#preview',
        '#weight',
        '#printed',
    ];

    private RenderCallbackResolver $renderCallbackResolver;

    public function __construct(RenderCallbackResolver $renderCallbackResolver)
    {
        $this->renderCallbackResolver = $renderCallbackResolver;
    }

    public function getNodeType(): string
    {
        return Array_::class;
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->mayHaveLazyBuilderKeys($node)) {
            return [];
        }

        $itemsByKey = [];
        $hasUnknownKeys = false;
        $nextIntegerKey = 0;
        foreach ($node->items as $item) {
            if ($item->unpack) {
                $hasUnknownKeys = true;
                continue;
            }
            if ($item->key === null) {
                $key = $nextIntegerKey;
            } else {
                $keyValues = $scope->getType($item->key)->toArrayKey()->getConstantScalarValues();
                if (count($keyValues) !== 1 || (!is_int($keyValues[0]) && !is_string($keyValues[0]))) {
                    $hasUnknownKeys = true;
                    continue;
                }
                $key = $keyValues[0];
            }
            if (is_int($key)) {
                $nextIntegerKey = max($nextIntegerKey, $key + 1);
            }
            $itemsByKey[$key] = $item;
        }

        $lazyBuilder = $itemsByKey[self::LAZY_BUILDER] ?? null;
        if ($lazyBuilder !== null) {
            $lazyBuilderType = $scope->getType($lazyBuilder->value);
            // A key filter, like array_intersect_key($elements, ['#lazy_builder' => TRUE]),
            // is not a render array.
            if ($lazyBuilderType->isBoolean()->yes()) {
                return [];
            }
            // Renderer uses isset(), so NULL means there is no #lazy_builder.
            if ($lazyBuilderType->isNull()->yes()) {
                $lazyBuilder = null;
            }
        }
        if ($lazyBuilder === null) {
            return $hasUnknownKeys ? [] : $this->checkPlaceholderWithoutCallback($itemsByKey, $scope);
        }

        $errors = $this->checkStructure($lazyBuilder, $scope);
        if (!$hasUnknownKeys) {
            $errors = [...$errors, ...$this->checkUnsupportedKeys($itemsByKey, $lazyBuilder)];
        }
        return [...$errors, ...$this->checkCallbackReturnType($lazyBuilder, $scope)];
    }

    /**
     * Whether a key could be #lazy_builder or #create_placeholder.
     *
     * Most array literals have neither, so this skips them before resolving
     * any key types.
     */
    private function mayHaveLazyBuilderKeys(Array_ $node): bool
    {
        foreach ($node->items as $item) {
            if ($item->key === null || $item->key instanceof Int_) {
                continue;
            }
            if (!$item->key instanceof String_) {
                return true;
            }
            if ($item->key->value === self::LAZY_BUILDER || $item->key->value === self::CREATE_PLACEHOLDER) {
                return true;
            }
        }
        return false;
    }

    /**
     * Reports #create_placeholder without #lazy_builder.
     *
     * A literal with only supported keys may be merged into an element that
     * has #lazy_builder, like $build += ['#create_placeholder' => TRUE]. A
     * literal with other keys is wrong either way: adding #lazy_builder later
     * breaks the supported keys rule.
     *
     * @param array<array-key, ArrayItem> $itemsByKey
     * @return list<IdentifierRuleError>
     */
    private function checkPlaceholderWithoutCallback(array $itemsByKey, Scope $scope): array
    {
        $createPlaceholder = $itemsByKey[self::CREATE_PLACEHOLDER] ?? null;
        if ($createPlaceholder === null
            || !$scope->getType($createPlaceholder->value)->isTrue()->yes()
            || count($this->findUnsupportedKeys($itemsByKey)) === 0
        ) {
            return [];
        }
        return [
            RuleErrorBuilder::message('#create_placeholder is TRUE, but there is no #lazy_builder callback. Drupal throws a LogicException when it renders this element.')
                ->line($createPlaceholder->getStartLine())
                ->identifier('lazyBuilder.placeholderWithoutCallback')
                ->tip('Add a #lazy_builder callback that builds the element, or remove #create_placeholder.')
                ->build(),
        ];
    }

    /**
     * Checks a #lazy_builder array literal.
     *
     * RenderCallbackRule already reports a #lazy_builder value that is not an
     * array literal, so other values are skipped.
     *
     * @return list<IdentifierRuleError>
     */
    private function checkStructure(ArrayItem $lazyBuilder, Scope $scope): array
    {
        if (!$lazyBuilder->value instanceof Array_) {
            return [];
        }
        $constantArrays = $scope->getType($lazyBuilder->value)->getConstantArrays();
        if (count($constantArrays) !== 1) {
            return [];
        }
        $lazyBuilderArray = $constantArrays[0];
        $keys = array_map(
            static fn ($keyType) => $keyType->getValue(),
            $lazyBuilderArray->getKeyTypes()
        );
        sort($keys);
        if ($keys !== [0, 1] || count($lazyBuilderArray->getOptionalKeys()) > 0) {
            return [
                $this->buildStructureError(
                    $lazyBuilder,
                    '#lazy_builder must be an array of two values: the callback, and an array of arguments for it.'
                ),
            ];
        }

        $arguments = $lazyBuilderArray->getOffsetValueType(new ConstantIntegerType(1));
        if ($arguments->isArray()->no()) {
            return [
                $this->buildStructureError(
                    $lazyBuilder,
                    sprintf('The #lazy_builder arguments must be an array, %s given.', $arguments->describe(VerbosityLevel::typeOnly()))
                ),
            ];
        }

        $argumentArrays = $arguments->getConstantArrays();
        if (count($argumentArrays) !== 1) {
            $argument = $arguments->getIterableValueType();
            if (!$this->isNonScalarArgument($argument)) {
                return [];
            }
            return [
                $this->buildNonScalarArgumentError(
                    $lazyBuilder,
                    sprintf('#lazy_builder arguments contain %s, but only scalar values and NULL are allowed.', $argument->describe(VerbosityLevel::typeOnly()))
                ),
            ];
        }
        $argumentsArray = $argumentArrays[0];
        $errors = [];
        foreach ($argumentsArray->getValueTypes() as $position => $argument) {
            if (!$this->isNonScalarArgument($argument)) {
                continue;
            }
            $errors[] = $this->buildNonScalarArgumentError(
                $lazyBuilder,
                sprintf(
                    '#lazy_builder argument %s is %s, but only scalar values and NULL are allowed.',
                    $argumentsArray->getKeyTypes()[$position]->describe(VerbosityLevel::value()),
                    $argument->describe(VerbosityLevel::typeOnly())
                )
            );
        }
        return $errors;
    }

    /**
     * Mixed, never, and unions that may be scalar or NULL are not reported.
     */
    private function isNonScalarArgument(Type $argument): bool
    {
        return $argument->isScalar()->no()
            && $argument->isNull()->no()
            && !(new NeverType())->isSuperTypeOf($argument)->yes();
    }

    private function buildNonScalarArgumentError(ArrayItem $lazyBuilder, string $message): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->line($lazyBuilder->getStartLine())
            ->identifier('lazyBuilder.nonScalarArgument')
            ->tip('Pass an ID or another scalar value, and load the object in the callback. Drupal puts the arguments in the placeholder.')
            ->build();
    }

    private function buildStructureError(ArrayItem $lazyBuilder, string $message): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)
            ->line($lazyBuilder->getStartLine())
            ->identifier('lazyBuilder.invalidStructure')
            ->tip("Use ['#lazy_builder' => [callback, [arguments]]].")
            ->build();
    }

    /**
     * @param array<array-key, ArrayItem> $itemsByKey
     * @return list<IdentifierRuleError>
     */
    private function checkUnsupportedKeys(array $itemsByKey, ArrayItem $lazyBuilder): array
    {
        $unsupportedKeys = $this->findUnsupportedKeys($itemsByKey);
        if (count($unsupportedKeys) === 0) {
            return [];
        }
        return [
            RuleErrorBuilder::message(sprintf(
                'An element with #lazy_builder can only have %s next to it. Found: %s.',
                $this->describeAllowedKeys(),
                implode(', ', $unsupportedKeys)
            ))
                ->line($lazyBuilder->getStartLine())
                ->identifier('lazyBuilder.unsupportedProperty')
                ->tip('Return these properties and children from the #lazy_builder callback instead.')
                ->build(),
        ];
    }

    /**
     * Lists the keys allowed next to #lazy_builder, for messages.
     *
     * #printed is left out: only Renderer sets it.
     */
    private function describeAllowedKeys(): string
    {
        $allowedKeys = array_values(array_diff(self::SUPPORTED_KEYS, [self::LAZY_BUILDER, '#printed']));
        $lastKey = array_pop($allowedKeys);
        return implode(', ', $allowedKeys) . ' and ' . $lastKey;
    }

    /**
     * @param array<array-key, ArrayItem> $itemsByKey
     * @return list<string>
     */
    private function findUnsupportedKeys(array $itemsByKey): array
    {
        return array_values(array_diff(array_map('strval', array_keys($itemsByKey)), self::SUPPORTED_KEYS));
    }

    /**
     * @param Scope&DependencyTracker $scope
     * @return list<IdentifierRuleError>
     */
    private function checkCallbackReturnType(ArrayItem $lazyBuilder, Scope $scope): array
    {
        $callbackExpressions = $this->renderCallbackResolver->findCallbackExpressions(self::LAZY_BUILDER, $lazyBuilder->value);
        if ($callbackExpressions === null || count($callbackExpressions) === 0) {
            return [];
        }
        $errors = [];
        foreach ($this->renderCallbackResolver->resolveType($callbackExpressions[0], $scope)->getConstantArrays() as $callbackArray) {
            if (!$callbackArray->isCallable()->yes()) {
                continue;
            }
            foreach ($callbackArray->findTypeAndMethodNames() as $typeAndMethodName) {
                $type = $typeAndMethodName->getType();
                $methodName = $typeAndMethodName->getMethod();
                if ($typeAndMethodName->isUnknown() || !$type->hasMethod($methodName)->yes()) {
                    continue;
                }
                $method = $type->getMethod($methodName, $scope);
                $returnType = ParametersAcceptorSelector::combineAcceptors($method->getVariants())->getReturnType();
                // A method that always throws never returns.
                if (!$returnType->isArray()->no() || (new NeverType())->isSuperTypeOf($returnType)->yes()) {
                    continue;
                }
                $errors[] = RuleErrorBuilder::message(sprintf(
                    '#lazy_builder callback %s::%s() returns %s, but it must return a render array.',
                    $method->getDeclaringClass()->getName(),
                    $method->getName(),
                    $returnType->describe(VerbosityLevel::typeOnly())
                ))
                    ->line($lazyBuilder->getStartLine())
                    ->identifier('lazyBuilder.nonArrayReturn')
                    ->build();
            }
        }
        return $errors;
    }
}
