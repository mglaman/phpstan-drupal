<?php declare(strict_types=1);

namespace PHPStan\Rules\Drupal;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\Php\PhpFunctionReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\VerbosityLevel;

final class PreRenderCallbackRule implements Rule
{

    /** @var Broker */
    private $broker;

    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

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
        // @see https://www.drupal.org/node/2966725
        $keysToCheck = ['#pre_render', '#post_render', '#lazy_builder', '#access_callback'];
        if (!in_array($key->value, $keysToCheck, true)) {
            return [];
        }

        $value = $node->value;
        if (!$value instanceof Node\Expr\Array_) {
            return [
                'The "#pre_render" render array value expects an array of callbacks.'
            ];
        }
        if (count($value->items) === 0) {
            return [];
        }

        $errors = [];
        foreach ($value->items as $pos => $item) {
            if ($item === null) {
                continue;
            }
            $preRenderItemValue = $item->value;
            if ($preRenderItemValue instanceof Node\Expr\Array_) {
                $stop = null;
                // @todo.
            }
            elseif ($preRenderItemValue instanceof Node\Scalar\String_) {
                $type = new ConstantStringType($preRenderItemValue->value);
                if ($type->isCallable()->no()) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf("#pre_render callback %s at key '%s' is not callable.", $type->describe(VerbosityLevel::value()), $pos)
                    )->line($preRenderItemValue->getLine())->build();
                }
                elseif ($this->broker->hasFunction(new \PhpParser\Node\Name($type->getValue()), null)) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf("#pre_render callback %s at key '%s' is not trusted. See https://www.drupal.org/node/2966725.", $type->describe(VerbosityLevel::value()), $pos)
                    )->line($preRenderItemValue->getLine())->build();
                } else {
                    // @see \PHPStan\Type\Constant\ConstantStringType::isCallable
                    preg_match('#^([a-zA-Z_\\x7f-\\xff\\\\][a-zA-Z0-9_\\x7f-\\xff\\\\]*)::([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)\\z#', $type->getValue(), $matches);
                    if ($matches !== null) {
                        $classRef = $this->broker->getClass($matches[1]);
                        if (!$classRef->implementsInterface('Drupal\Core\Security\TrustedCallbackInterface')) {
                            $errors[] = RuleErrorBuilder::message(
                                sprintf("#pre_render callback class '%s' at key '%s' does not implement Drupal\Core\Security\TrustedCallbackInterface.", $matches[1], $pos)
                            )->line($preRenderItemValue->getLine())->build();
                        }
                        // @todo inspect if the method is listed as a trusted callback.
                        // @todo make sure it's called correctly (static or not.)
                        $methodRef = $classRef->hasMethod($matches[2]);
                    }
                }
            }
            elseif ($preRenderItemValue instanceof Node\Expr\Closure) {
                // @todo do we need to more more inspection or will normal PHPStan rules cover this?
                // @todo check if form and if it will be serialized (go boom.)
                continue;
            }
            else {
                $type = $scope->getType($preRenderItemValue);
                $errors[] = RuleErrorBuilder::message(
                    sprintf("#pre_render value '%s' at key '%s' is invalid.", $type->describe(VerbosityLevel::value()), $pos)
                )->line($preRenderItemValue->getLine())->build();
            }
        }

        return $errors;
    }
}
