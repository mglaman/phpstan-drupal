<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

final class DummyRule implements \PHPStan\Rules\Rule
{
    public function getNodeType(): string
    {
        return 'PhpParser\Node\Expr\FuncCall';
    }

    public function processNode(Node $node, Scope $scope): array
    {
        return [];
    }
}
