<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use Drupal;
use mglaman\PHPStanDrupal\Internal\DeprecatedScopeCheck;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Symfony\Component\HttpFoundation\RequestStack as SymfonyRequestStack;
use function explode;
use function sprintf;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
final class RequestStackGetMainRequestRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (DeprecatedScopeCheck::inDeprecatedScope($scope)) {
            return [];
        }
        [$major, $minor] = explode('.', Drupal::VERSION, 3);
        // Only valid for 9.3 -> 9.5. Deprecated in Drupal 10.
        if (($major !== '9' || (int) $minor < 3)) {
            return [];
        }
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }
        $method_name = $node->name->toString();
        if ($method_name !== 'getMasterRequest') {
            return [];
        }
        $type = $scope->getType($node->var);
        $symfonyRequestStackType = new ObjectType(SymfonyRequestStack::class);
        if ($symfonyRequestStackType->isSuperTypeOf($type)->yes()) {
            $message = sprintf(
                '%s::getMasterRequest() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0 for Symfony 6 compatibility. Use the forward compatibility shim class %s and its getMainRequest() method instead.',
                SymfonyRequestStack::class,
                'Drupal\Core\Http\RequestStack'
            );
            return [
                RuleErrorBuilder::message($message)
                ->tip('Change record: https://www.drupal.org/node/3253744')
                ->build(),
            ];
        }
        return [];
    }
}
