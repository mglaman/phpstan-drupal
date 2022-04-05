<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\EntityQuery;

use mglaman\PHPStanDrupal\Type\EntityQuery\EntityQueryExecuteDoesNotHaveAccessCheckCountType;
use mglaman\PHPStanDrupal\Type\EntityQuery\EntityQueryExecuteDoesNotHaveAccessCheckType;
use mglaman\PHPStanDrupal\Type\EntityQuery\EntityQueryType;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function PHPStan\dumpType;

final class EntityQueryHasAccessCheckRule implements Rule
{
    public function getNodeType(): string
    {
        return \PhpParser\Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Node\Expr\MethodCall) {
            return [];
        }

        $name = $node->name;
        if (!$name instanceof Node\Identifier) {
            return [];
        }
        if ($name->toString() !== 'execute') {
            return [];
        }

        $type = $scope->getType($node);

        if (!$type instanceof EntityQueryExecuteDoesNotHaveAccessCheckCountType && !$type instanceof EntityQueryExecuteDoesNotHaveAccessCheckType) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Missing explicit access check on entity query.'
            )->tip('See https://www.drupal.org/node/3201242')->build(),
        ];
    }
}
