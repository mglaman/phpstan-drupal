# phpstan-drupal

[![Tests](https://github.com/mglaman/phpstan-drupal/actions/workflows/php.yml/badge.svg)](https://github.com/mglaman/phpstan-drupal/actions/workflows/php.yml) [![CircleCI](https://circleci.com/gh/mglaman/phpstan-drupal.svg?style=svg)](https://circleci.com/gh/mglaman/phpstan-drupal)

Extension for [PHPStan](https://phpstan.org/) to allow analysis of Drupal code.

PHPStan is able to [discover symbols](https://phpstan.org/user-guide/discovering-symbols) by using autoloading provided 
by Composer. However, Drupal does not provide autoloading information for modules and themes. This project registers 
those namespaces so that PHPStan can properly discover symbols in your Drupal code base automatically.

> [!NOTE]
> With Drupal 11.2, Drupal core is now using PHPStan 2.0. The 1.x branch of phpstan-drupal will be supported until 
> Drupal 10 loses security support when Drupal 12 is released.

## Sponsors

<a href="https://www.fame.fi/"><img src="https://www.fame.fi/assets/images/fame-logo.png" alt="Fame Helsinki" width="250" ></a>

[Would you like to sponsor?](https://github.com/sponsors/mglaman)

## Usage

When you are using [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer), `phpstan.neon` will be automatically included.

<details>
  <summary>Manual installation</summary>

If you don't want to use `phpstan/extension-installer`, include `extension.neon` in your project's PHPStan config:

```
includes:
    - vendor/mglaman/phpstan-drupal/extension.neon
```

To include Drupal specific analysis rules, include this file:

```
includes:
    - vendor/mglaman/phpstan-drupal/rules.neon
```
</details>

## Getting help

Ask for assistance in the [discussions](https://github.com/mglaman/phpstan-drupal/discussions) or [#phpstan](https://drupal.slack.com/archives/C033S2JUMLJ) channel on Drupal Slack.

## Excluding tests from analysis

To exclude tests from analysis, add the following parameter

```
parameters:
	excludePaths:
		- *Test.php
		- *TestBase.php
```

## Deprecation testing

This project depends on `phpstan/phpstan-deprecation-rules` which adds deprecation rules. We provide Drupal-specific 
deprecated scope resolvers.

To only handle deprecation testing, use a `phpstan.neon` like this:

```
parameters:
	customRulesetUsed: true
	reportUnmatchedIgnoredErrors: false
	# Ignore phpstan-drupal extension's rules.
	ignoreErrors:
		- '#\Drupal calls should be avoided in classes, use dependency injection instead#'
		- '#Plugin definitions cannot be altered.#'
		- '#Missing cache backend declaration for performance.#'
		- '#Plugin manager has cache backend specified but does not declare cache tags.#'
includes:
	- vendor/mglaman/phpstan-drupal/extension.neon
	- vendor/phpstan/phpstan-deprecation-rules/rules.neon
```

To disable deprecation rules while using `phpstan/extension-installer`, you can do the following:

```json
{
  "extra": {
    "phpstan/extension-installer": {
      "ignore": [
        "phpstan/phpstan-deprecation-rules"
      ]
    }
  }
}
```

See the `extension-installer` documentation for more information: https://github.com/phpstan/extension-installer#ignoring-a-particular-extension

## Adapting to your project

### Customizing rules

#### Opt-in rules

Several rules are disabled by default because they may produce noise on existing codebases or require additional setup. Most of them are enabled together via [bleedingEdge.neon](#bleeding-edge-checks); the list below lets you enable them individually.

```neon
parameters:
    drupal:
        rules:
            # Enforces that OOP hook implementations using the Hook attribute have the
            # correct method signature for hook_form_alter, hook_form_FORM_ID_alter, etc.
            # Requires Drupal 10.3+ (Hook attribute).
            hookRules: true

            # Flags non-abstract test classes whose names do not end with "Test".
            testClassSuffixNameRule: true

            # Flags properties that are private or read-only in classes using
            # DependencySerializationTrait, which does not support them.
            dependencySerializationTraitPropertyRule: true

            # Flags calls to AccessResult static methods (::allowed(), ::forbidden(), etc.)
            # whose argument type already makes the condition always true or always false.
            accessResultConditionRule: true

            # Flags addCacheableDependency() calls whose argument does not implement
            # CacheableDependencyInterface.
            cacheableDependencyRule: true

            # Flags logger channel objects (from LoggerChannelFactoryInterface::get()) that
            # are assigned to a property in a class using DependencySerializationTrait,
            # which cannot serialize logger channels correctly.
            loggerFromFactoryPropertyAssignmentRule: true

            # Flags direct injection of EntityStorageInterface (or a subtype) into a
            # constructor. Inject EntityTypeManagerInterface and call getStorage() instead.
            entityStorageDirectInjectionRule: true
```

#### Disabling checks for extending `@internal` classes

You can disable the `ClassExtendsInternalClassRule` rule by adding the following to your `phpstan.neon`:

```neon
parameters: 
    drupal:
        rules:
            classExtendsInternalClassRule: false
```

#### Disabling  extensions

You can disable various extensions. This is useful when contributing to Drupal Core to improve its types.

```neon
parameters:
    drupal:
        extensions:
            entityFieldsViaMagicReflection: true
            entityFieldMethodsViaMagicReflection: true
            entityQuery: true
            entityRepository: true
            stubFiles: true
```

Both options are enabled by default.

#### Bleeding-edge checks

`bleedingEdge.neon` is a convenience include that enables all rules and checks that are disabled by default. New rules are typically added here first and promoted to the default set in a future minor release, so including it lets you adopt upcoming checks early.

```neon
includes:
    - vendor/mglaman/phpstan-drupal/bleedingEdge.neon
```

Currently `bleedingEdge.neon` enables the following:

- `checkCoreDeprecatedHooksInApiFiles` — reports hook implementations deprecated in Drupal core `.api.php` files
- `checkContribDeprecatedHooksInApiFiles` — reports hook implementations deprecated in contrib module `.api.php` files
- `hookRules` — validates OOP hook method signatures (requires Drupal 10.3+)
- `testClassSuffixNameRule` — non-abstract test class names must end with `Test`
- `dependencySerializationTraitPropertyRule` — flags private or read-only properties in classes using `DependencySerializationTrait`
- `accessResultConditionRule` — flags always-true/always-false `AccessResult` conditions
- `cacheableDependencyRule` — flags `addCacheableDependency()` calls with non-cacheable arguments
- `loggerFromFactoryPropertyAssignmentRule` — flags logger channels assigned to properties in classes using `DependencySerializationTrait`

To enable individual checks instead of all of them, see the parameters above under [Opt-in rules](#opt-in-rules).

> [!NOTE]
> The older `checkDeprecatedHooksInApiFiles` parameter is deprecated and will be removed in a future release. Use
> `checkCoreDeprecatedHooksInApiFiles` and `checkContribDeprecatedHooksInApiFiles` instead.

#### Detecting @todo comments referencing the current Drupal.org issue (contrib CI)

`TodoCommentWithIssueUrlRule` is an opt-in rule for Drupal contrib CI pipelines. When running PHPStan as part of a GitLab merge request, it reports an error for any `@todo` comment that contains a drupal.org issue URL matching the current issue — for example:

```php
// @todo Remove once https://drupal.org/i/3456789 is resolved.
```

This prevents issue-specific TODOs from being accidentally merged without resolution.

The rule auto-detects the current issue NID from standard GitLab CI environment variables:

- `CI_MERGE_REQUEST_SOURCE_BRANCH_NAME` (e.g. `3456789-my-feature`)
- `CI_MERGE_REQUEST_SOURCE_PROJECT_PATH` (e.g. `issue/mymodule-3456789`)

It is silent when neither variable is set, so it is safe to include in a shared config.

The rule is **not registered by default**. To enable it, add it to your project's `phpstan.neon`:

```neon
rules:
    - mglaman\PHPStanDrupal\Rules\Drupal\TodoCommentWithIssueUrlRule
```

> [!NOTE]
> When using the [Drupal GitLab CI templates](https://project.pages.drupalcode.org/gitlab_templates/jobs/phpstan/),
> adding extra rules requires a custom `phpstan.neon` that includes the default configuration, since adding additional
> rules is not supported directly through the template variables.

Both `drupal.org/i/{nid}` and `drupal.org/project/{project}/issues/{nid}` URL formats are recognized.

### Entity storage mappings.

The `EntityTypeManagerGetStorageDynamicReturnTypeExtension` service helps map dynamic return types. This inspects the
passed entity type ID and tries to return a known storage class, besides the default `EntityStorageInterface`. The
default mapping can be found in `extension.neon`. For example:

```
parameters:
	drupal:
		entityMapping:
			block:
				class: Drupal\block\Entity\Block
				storage: Drupal\Core\Config\Entity\ConfigEntityStorage
			node:
				class: Drupal\node\Entity\Node
				storage: Drupal\node\NodeStorage
			taxonomy_term:
				class: Drupal\taxonomy\Entity\Term
				storage: Drupal\taxonomy\TermStorage
			user:
				class: Drupal\user\Entity\User
				storage: Drupal\user\UserStorage
```

To add support for custom entities, you may add the same definition in your project's `phpstan.neon`. See the following
example for adding a mapping for Search API:

```
parameters:
	drupal:
		entityMapping:
			search_api_index:
				class: Drupal\search_api\Entity\Index
				storage: Drupal\search_api\Entity\SearchApiConfigEntityStorage
			search_api_server:
				class: Drupal\search_api\Entity\Server
				storage: Drupal\search_api\Entity\SearchApiConfigEntityStorage			    
```

Similarly, the `EntityStorageDynamicReturnTypeExtension` service helps to determine the type of the entity which is
loaded, created etc.. when using an entity storage.
For instance when using

```php
$node = \Drupal::entityTypeManager()->getStorage('node')->create(['type' => 'page', 'title' => 'foo']);
```

It helps with knowing the type of the `$node` variable is `Drupal\node\Entity\Node`.

The default mapping can be found in `extension.neon`:

```neon
parameters:
	drupal:
		entityMapping:
			block:
				class: Drupal\block\Entity\Block
				storage: Drupal\Core\Config\Entity\ConfigEntityStorage
			node:
				class: Drupal\node\Entity\Node
				storage: Drupal\node\NodeStorage
			taxonomy_term:
				class: Drupal\taxonomy\Entity\Term
				storage: Drupal\taxonomy\TermStorage
			user:
				class: Drupal\user\Entity\User
				storage: Drupal\user\UserStorage
```

To add support for custom entities, you may add the same definition in your project's `phpstan.neon` likewise.

### Providing entity type mappings for a contrib module

Contributed modules can provide their own mapping that can be automatically registered with a user's code base when 
they use the `phpstan/extension-installer`.  The extension installer scans installed package's `composer.json` for a 
value in `extra.phpstan`. This will automatically bundle the defined include that contains an entity mapping 
configuration.

For example, the Paragraphs module could have the following `entity_mapping.neon` file:

```neon
parameters:
	drupal:
		entityMapping:
			paragraph:
				class: Drupal\paragraphs\Entity\Paragraph
			paragraphs_type:
				class: Drupal\paragraphs\Entity\ParagraphsType
```

Then in the `composer.json` for Paragraphs, the `entity_mapping.neon` would be provided as a PHPStan include

```json
{
  "name": "drupal/paragraphs",
  "description": "Enables the creation of Paragraphs entities.",
  "type": "drupal-module",
  "license": "GPL-2.0-or-later",
  "require": {
    "drupal/entity_reference_revisions": "~1.3"
  },
  "extra": {
    "phpstan": {
      "includes": [
        "entity_mapping.neon"
      ]
    }
  }
}

```
