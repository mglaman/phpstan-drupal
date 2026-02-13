---
name: gen-test
description: Generate a PHPStan rule test class and fixture data file
disable-model-invocation: true
arguments:
  - name: rule_class
    description: Fully qualified class name of the rule to test (e.g. mglaman\PHPStanDrupal\Rules\Drupal\EntityQuery\EntityQueryHasAccessCheckRule)
---

# Generate PHPStan Rule Test

Create a test class and fixture file for a PHPStan rule in this project.

## Steps

1. Read the rule class source to understand what it checks
2. Determine the appropriate test directory based on the rule's namespace:
   - `Rules\Classes\` -> `tests/src/Rules/`
   - `Rules\Deprecations\` -> `tests/src/Rules/`
   - `Rules\Drupal\` -> `tests/src/Rules/`
   - `Rules\Drush\` -> `tests/src/Rules/`
   - `Type\` -> `tests/src/Type/`
3. Create the test class following these conventions:
   - Extend `mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase`
   - Use `declare(strict_types=1)` at the top
   - Mark the class as `final`
   - Implement `getRule()` returning an instance of the rule
   - Use `@dataProvider cases` with a generator
   - Each case yields `[files, errors]` where errors are `[message, line, tip?]`
4. Create fixture PHP file(s) in `tests/src/Rules/data/` or `tests/src/Type/data/`
5. Run the test with `php vendor/bin/phpunit --filter=TheNewTestClass` to verify

## Example test structure

```php
<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\ExampleRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class ExampleRuleTest extends DrupalRuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new ExampleRule();
    }

    /**
     * @dataProvider cases
     *
     * @param list<array{0: string, 1: int, 2?: string|null}> $errors
     */
    public function test(array $files, array $errors): void
    {
        $this->analyse($files, $errors);
    }

    public static function cases(): \Generator
    {
        yield 'passing case' => [
            [__DIR__ . '/data/example-pass.php'],
            [],
        ];
        yield 'failing case' => [
            [__DIR__ . '/data/example-fail.php'],
            [
                ['Error message here.', 10],
            ],
        ];
    }
}
```
