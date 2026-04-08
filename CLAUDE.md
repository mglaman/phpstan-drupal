# phpstan-drupal

PHPStan extension and rules for Drupal static analysis.

## Key Commands

- Run all tests: `php vendor/bin/phpunit`
- Run single test: `php vendor/bin/phpunit --filter=TestClassName`
- Run single test method: `php vendor/bin/phpunit --filter=TestClassName::testMethod`
- Lint (check): `php vendor/bin/phpcs`
- Lint (fix): `php vendor/bin/phpcbf`
- Static analysis: `php vendor/bin/phpstan analyze`

## Architecture

- `src/Rules/` - Custom PHPStan rules (organized by: Classes, Deprecations, Drupal, Drush)
- `src/Type/` - Type extensions for Drupal APIs (entity queries, service containers, etc.)
- `src/Drupal/` - Drupal autoloader, service map, entity data, extension discovery
- `src/DeprecatedScope/` - Deprecation scope handling
- `src/Reflection/` - Reflection extensions
- `extension.neon` - PHPStan service definitions for type extensions
- `rules.neon` - PHPStan service definitions for rules

## Testing Conventions

- Rule tests extend `DrupalRuleTestCase` (which extends PHPStan's `RuleTestCase`)
- Type tests extend PHPStan's `TypeInferenceTestCase`
- Test classes live in `tests/src/Rules/` and `tests/src/Type/`
- Test fixture/data files live in `tests/src/Rules/data/` and `tests/src/Type/data/`
- Tests use `$this->analyse()` with fixture file paths and expected error arrays
- Each error is `[message, line, tip?]`
- Many tests use `@dataProvider` with generators yielding `[files, errors]`

## Coding Standards

- PSR-2 base with Slevomat coding standard additions
- Alphabetically sorted use statements (PSR-12 compatible)
- PHPCS only runs on `src/` (not tests)
- `declare(strict_types=1)` in all files
