# phpstan-drupal

[![Tests](https://github.com/mglaman/phpstan-drupal/actions/workflows/php.yml/badge.svg)](https://github.com/mglaman/phpstan-drupal/actions/workflows/php.yml) [![CircleCI](https://circleci.com/gh/mglaman/phpstan-drupal.svg?style=svg)](https://circleci.com/gh/mglaman/phpstan-drupal)

Extension for [PHPStan](https://phpstan.org/) to allow analysis of Drupal code.

## Sponsors

<a href="https://www.undpaul.de/"><img src="https://www.undpaul.de/themes/custom/undpaul3/logo.svg" alt="undpaul" width="250" /></a>

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

Add the deprecation rules to your Drupal project's dependencies

```
composer require --dev phpstan/phpstan-deprecation-rules
```

Edit your `phpstan.neon` to look like the following:

```
includes:
	- vendor/mglaman/phpstan-drupal/extension.neon
	- vendor/phpstan/phpstan-deprecation-rules/rules.neon
```

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

## Adapting to your project

### Specifying your Drupal project's root

By default, the PHPStan Drupal extension will try to determine your Drupal project's root directory based on the working
directory that PHPStan is checking. If this is not working properly, you can explicitly define the Drupal project's root
directory using the `drupal.drupal_root` parameter.

```
parameters:
	drupal:
		drupal_root: /path/to/drupal
```

You can also use container parameters. For instance you can always set it to the current working directory.

```
parameters:
	drupal:
		drupal_root: %currentWorkingDirectory%
```

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
