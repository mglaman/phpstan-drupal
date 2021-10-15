<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\Coder;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * Based on Drupal_Sniffs_Functions_DiscouragedFunctionsSniff.
 */
class DiscouragedFunctionsRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        assert($node instanceof FuncCall);

        if (!($node->name instanceof Node\Name)) {
            return [];
        }
        $name = strtolower((string)$node->name);

        $discouragedFunctions = [
            // Devel module debugging functions.
            'dargs',
            'dcp',
            'dd',
            'dfb',
            'dfbt',
            'dpm',
            'dpq',
            'dpr',
            'dprint_r',
            'drupal_debug',
            'dsm',
            'dvm',
            'dvr',
            'kdevel_print_object',
            'kpr',
            'kprint_r',
            'sdpm',
            // Functions which are not available on all
            // PHP builds.
            'fnmatch',
            // Functions which are a security risk.
            'eval',
        ];

        if (in_array($name, $discouragedFunctions, true)) {
            return [sprintf('Calls to function %s should not exist.', $name)];
        }
        return [];
    }
}
