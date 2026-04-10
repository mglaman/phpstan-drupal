<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * @implements Rule<Node\Stmt\ClassMethod>
 */
final class EntityStorageDirectInjectionRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Stmt\ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name->toString() !== '__construct') {
            return [];
        }
        if (!$scope->isInClass()) {
            return [];
        }

        $storageType = new ObjectType(EntityStorageInterface::class);
        $errors = [];

        foreach ($node->params as $param) {
            if ($param->type === null) {
                continue;
            }

            $paramType = $scope->getFunctionType($param->type, false, false);
            if (!$storageType->isSuperTypeOf($paramType)->yes()) {
                continue;
            }

            $paramName = $param->var instanceof Variable && is_string($param->var->name)
                ? '$' . $param->var->name
                : 'parameter';

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Direct injection of entity storage via %s is not recommended. Inject %s and call getStorage() at the call-site instead.',
                    $paramName,
                    EntityTypeManagerInterface::class
                )
            )
                ->line($param->getStartLine())
                ->identifier('drupal.entityStorageDirectInjection')
                ->tip('See https://mglaman.dev/blog/dependency-injection-anti-patterns-drupal')
                ->build();
        }

        return $errors;
    }
}
