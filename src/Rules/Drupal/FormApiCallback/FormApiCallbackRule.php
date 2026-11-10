<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\FormApiCallback;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use function in_array;

/**
 * Reports Form API callbacks in array literals that fail at runtime.
 *
 * Covers '#submit' => [...] and '#ajax' => ['callback' => ...].
 *
 * @implements Rule<ArrayItem>
 */
final class FormApiCallbackRule implements Rule
{

    private FormApiCallbackChecker $checker;

    public function __construct(FormApiCallbackChecker $checker)
    {
        $this->checker = $checker;
    }

    public function getNodeType(): string
    {
        return ArrayItem::class;
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $key = $node->key;
        if (!$key instanceof String_ || !$node->value instanceof Array_) {
            return [];
        }
        if (in_array($key->value, FormApiCallbackChecker::CALLBACK_LIST_KEYS, true)) {
            return $this->checker->checkCallbackList($key->value, $node->value, $scope);
        }
        if ($key->value === FormApiCallbackChecker::AJAX_KEY) {
            return $this->checker->checkAjaxSettings($node->value, $scope);
        }
        return [];
    }
}
