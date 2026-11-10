<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Reflection\ClassReflection;
use function array_key_exists;
use function count;
use function file_get_contents;

/**
 * Reads the method names a class returns from trustedCallbacks().
 *
 * The method is never called. PHPStan can reflect classes that PHP cannot
 * load, such as a second class declared in a test file, so the method body is
 * parsed instead.
 */
final class TrustedCallbacksReader
{

    private ?Parser $parser = null;

    /**
     * @var array<string, list<string>|null>
     */
    private array $methodNamesByLocation = [];

    /**
     * @return list<string>|null
     *   The method names, or NULL when trustedCallbacks() does not only return
     *   an array of string literals.
     */
    public function read(ClassReflection $classReflection, DependencyTracker $dependencyTracker): ?array
    {
        if (!$classReflection->hasNativeMethod('trustedCallbacks')) {
            return null;
        }
        $method = $classReflection->getNativeReflection()->getMethod('trustedCallbacks');
        $fileName = $method->getFileName();
        $startLine = $method->getStartLine();
        if ($fileName === false || $startLine === false) {
            return null;
        }
        $dependencyTracker->trackFileDependency($fileName);

        $location = $fileName . ':' . $startLine;
        if (!array_key_exists($location, $this->methodNamesByLocation)) {
            $this->methodNamesByLocation[$location] = $this->readFromFile($fileName, $startLine);
        }
        return $this->methodNamesByLocation[$location];
    }

    /**
     * @return list<string>|null
     */
    private function readFromFile(string $fileName, int $startLine): ?array
    {
        $code = @file_get_contents($fileName);
        if ($code === false) {
            return null;
        }
        try {
            $stmts = $this->getParser()->parse($code);
        } catch (Error $e) {
            return null;
        }
        if ($stmts === null) {
            return null;
        }

        $method = (new NodeFinder())->findFirst(
            $stmts,
            static fn (Node $node): bool => $node instanceof ClassMethod
                && $node->name->toLowerString() === 'trustedcallbacks'
                && $node->getStartLine() === $startLine
        );
        if (!$method instanceof ClassMethod || $method->stmts === null || count($method->stmts) !== 1) {
            return null;
        }
        $return = $method->stmts[0];
        if (!$return instanceof Return_ || !$return->expr instanceof Array_) {
            return null;
        }

        $methodNames = [];
        foreach ($return->expr->items as $item) {
            if ($item->unpack || !$item->value instanceof String_) {
                return null;
            }
            $methodNames[] = $item->value->value;
        }
        return $methodNames;
    }

    private function getParser(): Parser
    {
        if ($this->parser === null) {
            $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        }
        return $this->parser;
    }
}
