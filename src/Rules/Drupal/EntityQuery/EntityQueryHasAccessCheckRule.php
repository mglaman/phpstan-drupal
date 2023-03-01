<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\EntityQuery;

use mglaman\PHPStanDrupal\Type\EntityQuery\ConfigEntityQueryType;
use mglaman\PHPStanDrupal\Type\EntityQuery\EntityQueryExecuteWithoutAccessCheckCountType;
use mglaman\PHPStanDrupal\Type\EntityQuery\EntityQueryExecuteWithoutAccessCheckType;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

final class EntityQueryHasAccessCheckRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
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

        if (!$type instanceof EntityQueryExecuteWithoutAccessCheckCountType && !$type instanceof EntityQueryExecuteWithoutAccessCheckType) {
            return [];
        }

        $parent = $scope->getType($node->var);
        if ($parent instanceof ConfigEntityQueryType) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Relying on entity queries to check access by default is deprecated in drupal:9.2.0 and an error will be thrown from drupal:10.0.0. Call \Drupal\Core\Entity\Query\QueryInterface::accessCheck() with TRUE or FALSE to specify whether access should be checked.'
            )->tip('See https://www.drupal.org/node/3201242')->build(),
        ];
    }
}
