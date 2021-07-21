<?php declare(strict_types=1);

namespace PHPStan\Rules\Drupal;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

final class PreRenderCallbackRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Expr\ArrayItem::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\ArrayItem);
        $key = $node->key;
        if (!$key instanceof Node\Scalar\String_) {
            return [];
        }
        if ($key->value !== '#pre_render') {
            return [];
        }
        $value = $node->value;
        if (!$value instanceof Node\Expr\Array_) {
            return [
                'The "#pre_render" render array value expects an array of callbacks'
            ];
        }

        $errors = [];
        foreach ($value->items as $item) {
            if ($item === null) {
                continue;
            }
            $preRenderItemValue = $item->value;
            if ($preRenderItemValue instanceof Node\Expr\Array_) {
                // @todo.
            }
            elseif ($preRenderItemValue instanceof Node\Scalar\String_) {
                // @todo look up the function.
            }
            elseif ($preRenderItemValue instanceof Node\Expr\Closure) {
                // @todo anything at all?
            }
            else {
                // @todo report error because it's unexpected?
            }
            $stop = null;
        }

        return $errors;
    }
}
