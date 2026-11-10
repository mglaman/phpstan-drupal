<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\FormApiCallback;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use function in_array;

/**
 * Reports Form API callbacks assigned into a form array that fail at runtime.
 *
 * Covers the assignments form alters use:
 * - $form['#submit'] = [...]
 * - $form['#submit'][] = ...
 * - $form['#ajax'] = ['callback' => ...]
 * - $form['#ajax']['callback'] = ...
 *
 * @implements Rule<Assign>
 */
final class FormApiCallbackAssignRule implements Rule
{

    private FormApiCallbackChecker $checker;

    public function __construct(FormApiCallbackChecker $checker)
    {
        $this->checker = $checker;
    }

    public function getNodeType(): string
    {
        return Assign::class;
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $target = $node->var;
        if (!$target instanceof ArrayDimFetch) {
            return [];
        }

        $key = $target->dim;
        if ($key instanceof String_) {
            if (in_array($key->value, FormApiCallbackChecker::CALLBACK_LIST_KEYS, true)) {
                return $node->expr instanceof Array_ ? $this->checker->checkCallbackList($key->value, $node->expr, $scope) : [];
            }
            if ($key->value === FormApiCallbackChecker::AJAX_KEY) {
                return $node->expr instanceof Array_ ? $this->checker->checkAjaxSettings($node->expr, $scope) : [];
            }
        }

        $parent = $target->var;
        if (!$parent instanceof ArrayDimFetch || !$parent->dim instanceof String_) {
            return [];
        }
        $parentKey = $parent->dim->value;
        if (in_array($parentKey, FormApiCallbackChecker::CALLBACK_LIST_KEYS, true)) {
            return $this->checker->checkCallback($parentKey, $node->expr, $scope);
        }
        if ($key instanceof String_
            && $key->value === FormApiCallbackChecker::AJAX_CALLBACK_KEY
            && $parentKey === FormApiCallbackChecker::AJAX_KEY
        ) {
            return $this->checker->checkCallback($parentKey, $node->expr, $scope);
        }
        return [];
    }
}
