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

## Registering Rules

Pick the registration based on what the rule reports:

- **Deprecations** (code that breaks on the next Drupal major version): add a `services:` entry in `rules.neon` and a `conditionalTags` entry with `phpstan.rules.rule: %deprecationRulesInstalled%`. Don't add a `drupal.rules.*` toggle. The rule is on by default and turns off when a project turns off `phpstan/phpstan-deprecation-rules`. See https://github.com/mglaman/phpstan-drupal/issues/1080.
- **Practices a project can disagree with** (dependency injection, cache backends): add a `drupal.rules.*` parameter with a default and a `boolean()` schema entry in `extension.neon`, a `conditionalTags` entry in `rules.neon`, and a README entry.
- **Code that fails at runtime on every supported Drupal version**: add it to the `rules:` list in `rules.neon`.

A deprecation rule must:

- Only run on Drupal versions where the replacement exists, so every report can be fixed. This can be earlier than the deprecation: attribute discovery for plugin managers exists from Drupal 10.2, but core deprecated annotation-only managers in 11.2.
- Skip code inside `DeprecationHelper::backwardsCompatibleCall()` callbacks.
- Use core's wording: "is deprecated in drupal:X.Y.Z and is removed from drupal:A.B.C". Upgrade Status sorts errors by it.
- Have a stable identifier. Document known false positives in the README, with an `ignoreErrors` example that uses the identifier. A known false positive is not a reason to make the rule opt-in.

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
