# AGENTS.md

Guidance for AI agents and contributors working on phpstan-drupal. For commands
and architecture see `CLAUDE.md`; this file documents the rule registration
conventions, which are enforced by `tests/src/RuleConventionsTest.php`.

## Adding a new rule

Every new rule must be **toggleable**. Never add a new rule directly under
`rules:` in `rules.neon` — that section is frozen for legacy rules only and the
conventions test fails if it grows.

To add a rule named `fooBarRule`:

1. **`rules.neon`** — register the rule class under both `services:` and
   `conditionalTags:`:

   ```neon
   conditionalTags:
       mglaman\PHPStanDrupal\Rules\Drupal\FooBarRule:
           phpstan.rules.rule: %drupal.rules.fooBarRule%

   services:
       -
           class: mglaman\PHPStanDrupal\Rules\Drupal\FooBarRule
   ```

2. **`extension.neon`** — add the parameter under `parameters.drupal.rules`
   with a default of `false` (opt-in), and add it to `parametersSchema`:

   ```neon
   parameters:
       drupal:
           rules:
               fooBarRule: false
   parametersSchema:
       drupal: structure([
           rules: structure([
               fooBarRule: boolean()
           ])
       ])
   ```

3. **`bleedingEdge.neon`** — enable it so bleeding-edge users get it before it
   graduates:

   ```neon
   parameters:
       drupal:
           rules:
               fooBarRule: true
   ```

4. Document the rule under "Opt-in rules" in `README.md`.

### Exception: opt-in rules excluded from bleeding edge

An opt-in rule that is known to be too noisy or unstable to inflict on
bleeding-edge users may be deliberately kept out of `bleedingEdge.neon`. It then
stays available only through explicit per-rule configuration. Record the
exception by adding its parameter name to
`RuleConventionsTest::OPT_IN_RULES_EXCLUDED_FROM_BLEEDING_EDGE` (with a comment
explaining why) instead of adding it to `bleedingEdge.neon` in step 3. The path
back is to remove it from that list and add it to `bleedingEdge.neon`.

## Graduating a rule to the default ruleset

When an opt-in rule is stable enough to be on by default:

1. In `extension.neon`, flip its default from `false` to `true`. It stays in
   `conditionalTags`, so it remains opt-out (users can still disable it).
2. Remove its entry from `bleedingEdge.neon` — it is on by default now and must
   not be listed there.
3. Add its parameter name to `RuleConventionsTest::GRADUATED_RULES`, in the same
   change, to record that the `true` default is intentional.

## Convention summary

| Location in `rules.neon` | Default in `extension.neon` | Meaning | In `bleedingEdge.neon`? |
|---|---|---|---|
| `conditionalTags` | `false` | Opt-in, not yet default | **Yes**, unless excluded (see above) |
| `conditionalTags` | `true` | Graduated, still toggleable (opt-out) | No |
| `rules:` directly | — | Legacy, not toggleable — **no new additions** | — |

## Enforcement

`tests/src/RuleConventionsTest.php` parses the three neon files and asserts the
table above. It runs as part of the normal `phpunit` suite, is cheap, and stays
green until a convention is violated. If it fails, the failure message names the
exact rule/parameter and the fix — follow it rather than weakening the test.
