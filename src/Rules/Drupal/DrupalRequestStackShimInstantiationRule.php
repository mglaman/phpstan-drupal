<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use mglaman\PHPStanDrupal\Internal\DeprecatedScopeCheck;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

final class DrupalRequestStackShimInstantiationRule implements Rule
{

    public function getNodeType(): string
    {
        return Node\Expr\New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof Node\Expr\New_);
        if (DeprecatedScopeCheck::inDeprecatedScope($scope)) {
            return [];
        }
        [$major, $minor] = explode('.', \Drupal::VERSION, 3);
        // Only valid for 9.3 -> 9.5. Deprecated in Drupal 10.
        if ($major !== '9' || (int) $minor < 3) {
            return [];
        }

        $symfonyRequestStackType = new ObjectType(\Symfony\Component\HttpFoundation\RequestStack::class);
        if ($scope->getType($node)->equals($symfonyRequestStackType)) {
            return [
                RuleErrorBuilder::message('Do not instantiate Symfony\Component\HttpFoundation\RequestStack. Use \Drupal\Core\Http\RequestStack instead for forward compatibility to Symfony 5.')
                    ->tip('Change record: https://www.drupal.org/node/3253744')
                    ->build(),
            ];
        }
        return [];
    }
}
