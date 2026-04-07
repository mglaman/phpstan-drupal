<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal;

use PhpParser\Comment;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Detects @todo comments that reference the current Drupal.org issue.
 *
 * Intended for use in Drupal contrib CI pipelines. When a merge request is
 * open for a specific issue, any @todo referencing that issue's URL should be
 * resolved before the MR is merged.
 *
 * The current issue NID is detected from GitLab CI environment variables:
 *   - CI_MERGE_REQUEST_SOURCE_BRANCH_NAME (e.g. "3580523-prompt-user-to")
 *   - CI_MERGE_REQUEST_SOURCE_PROJECT_PATH (e.g. "issue/canvas-3580523")
 *
 * This rule is not registered by default. To use it, add to your phpstan.neon:
 *
 * @code
 * rules:
 *   - mglaman\PHPStanDrupal\Rules\Drupal\TodoCommentWithIssueUrlRule
 * @endcode
 *
 * @implements Rule<FileNode>
 */
final class TodoCommentWithIssueUrlRule implements Rule
{

    private ?int $issueId;

    public function __construct(?int $issueId = null)
    {
        $this->issueId = $issueId ?? self::detectIssueIdFromEnvironment();
    }

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->issueId === null) {
            return [];
        }

        /** @var FileNode $node */
        $comments = $this->collectAllComments($node->getNodes());
        $errors = [];
        foreach ($comments as [$comment, $line]) {
            $result = $this->checkComment($comment, $line);
            if ($result !== null) {
                $errors[] = $result;
            }
        }
        return $errors;
    }

    /**
     * @param Node[] $nodes
     * @return array<array{Comment, int}>
     */
    private function collectAllComments(array $nodes): array
    {
        $collected = [];
        $seen = [];
        $this->traverseNodes($nodes, $collected, $seen);
        return $collected;
    }

    /**
     * @param array<mixed> $nodes
     * @param array<array{Comment, int}> $collected
     * @param array<int, true> $seen
     */
    private function traverseNodes(array $nodes, array &$collected, array &$seen): void
    {
        foreach ($nodes as $node) {
            if (!$node instanceof Node) {
                continue;
            }
            foreach ($node->getComments() as $comment) {
                $line = $comment->getStartLine();
                if (isset($seen[$line])) {
                    continue;
                }
                $seen[$line] = true;
                $collected[] = [$comment, $line];
            }
            // PHP-Parser sub-nodes are public properties; get_object_vars avoids dynamic property access.
            $vars = get_object_vars($node);
            foreach ($node->getSubNodeNames() as $subNodeName) {
                if (!array_key_exists($subNodeName, $vars)) {
                    continue;
                }
                $subNode = $vars[$subNodeName];
                if ($subNode instanceof Node) {
                    $this->traverseNodes([$subNode], $collected, $seen);
                } elseif (is_array($subNode)) {
                    $this->traverseNodes($subNode, $collected, $seen);
                }
            }
        }
    }

    private function checkComment(Comment $comment, int $line): ?IdentifierRuleError
    {
        $text = $comment->getText();

        if (stripos($text, '@todo') === false) {
            return null;
        }

        $pattern = '#drupal\.org/(?:i/|project/[^/\s]+/issues/)(\d+)#i';
        if (preg_match_all($pattern, $text, $matches) === 0) {
            return null;
        }

        foreach ($matches[1] as $nid) {
            if ((int) $nid === $this->issueId) {
                return RuleErrorBuilder::message(sprintf(
                    '@todo references the current issue #%d.',
                    $this->issueId
                ))
                    ->line($line)
                    ->identifier('drupal.todoCurrentIssue')
                    ->build();
            }
        }

        return null;
    }

    private static function detectIssueIdFromEnvironment(): ?int
    {
        // CI_MERGE_REQUEST_SOURCE_BRANCH_NAME=3580523-prompt-user-to
        $branchName = getenv('CI_MERGE_REQUEST_SOURCE_BRANCH_NAME');
        if (is_string($branchName) && preg_match('/^(\d+)/', $branchName, $matches) === 1) {
            return (int) $matches[1];
        }

        // CI_MERGE_REQUEST_SOURCE_PROJECT_PATH=issue/canvas-3580523
        $projectPath = getenv('CI_MERGE_REQUEST_SOURCE_PROJECT_PATH');
        if (is_string($projectPath) && preg_match('/-(\d+)$/', $projectPath, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
