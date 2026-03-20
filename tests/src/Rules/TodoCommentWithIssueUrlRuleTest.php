<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\TodoCommentWithIssueUrlRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class TodoCommentWithIssueUrlRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new TodoCommentWithIssueUrlRule(3456789);
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/todo-comment-with-issue-url.php'],
            [
                [
                    '@todo references the current issue #3456789.',
                    5,
                ],
                [
                    '@todo references the current issue #3456789.',
                    11,
                ],
                [
                    '@todo references the current issue #3456789.',
                    23,
                ],
            ]
        );
    }


}
