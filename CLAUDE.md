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

## Rule Registration Conventions

Every rule must be **toggleable**. Never add a new rule directly under `rules:`
in `rules.neon` — that section is frozen for legacy rules and
`tests/src/RuleConventionsTest.php` fails if it grows.

To add a rule `fooBarRule`:

- `rules.neon` - register the class under both `services:` and `conditionalTags:`
  (`phpstan.rules.rule: %drupal.rules.fooBarRule%`)
- `extension.neon` - add `fooBarRule: false` under `parameters.drupal.rules` and
  `fooBarRule: boolean()` under `parametersSchema.drupal...rules`
- `bleedingEdge.neon` - add `fooBarRule: true` so bleeding-edge users get it
- Document it under "Opt-in rules" in `README.md`

Graduating an opt-in rule to default: flip its `extension.neon` default
`false` → `true`, remove it from `bleedingEdge.neon`, and add its parameter name
to `RuleConventionsTest::GRADUATED_RULES` in the same change. It stays in
`conditionalTags` so it remains opt-out.

A rule too noisy/unstable for bleeding edge may be kept out of
`bleedingEdge.neon` by listing it in
`RuleConventionsTest::OPT_IN_RULES_EXCLUDED_FROM_BLEEDING_EDGE` (with a reason)
instead of step 3.

| `rules.neon` location | `extension.neon` default | Meaning | In `bleedingEdge.neon`? |
|---|---|---|---|
| `conditionalTags` | `false` | Opt-in, not yet default | Yes, unless excluded above |
| `conditionalTags` | `true` | Graduated, still opt-out | No |
| `rules:` directly | — | Legacy, not toggleable — no new additions | — |

## Coding Standards

- PSR-2 base with Slevomat coding standard additions
- Alphabetically sorted use statements (PSR-12 compatible)
- PHPCS only runs on `src/` (not tests)
- `declare(strict_types=1)` in all files
