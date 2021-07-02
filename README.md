# phpstan-drupal

[![Tests](https://github.com/mglaman/phpstan-drupal/actions/workflows/php.yml/badge.svg)](https://github.com/mglaman/phpstan-drupal/actions/workflows/php.yml) [![CircleCI](https://circleci.com/gh/mglaman/phpstan-drupal.svg?style=svg)](https://circleci.com/gh/mglaman/phpstan-drupal)

Extension for [PHPStan](https://phpstan.org/) to allow analysis of Drupal code.

## Sponsors

<a href="https://www.undpaul.de/"><img src="https://www.undpaul.de/themes/custom/undpaul3/logo.svg" alt="undpaul" width="250" /></a> <a href="https://www.intracto.com/"><img src="https://digidak.be/wp-content/uploads/2020/03/logo-intracto-base-positief-grijs-blauw@4x-rgb.png" alt="Intracto" width="225" /></a>

[Would you like to sponsor?](https://github.com/sponsors/mglaman)

## Usage

When you are using [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer), `phpstan.neon` will be automatically included.

Otherwise add `phpstan.neon` to your Drupal project.

Make sure it has

```neon
includes:
	- vendor/mglaman/phpstan-drupal/extension.neon
```

## Enabling rules one-by-one

If you don't want to start using all the available strict rules at once but only one or two, you can! Just don't include
the whole `rules.neon` from this package in your configuration, but look at its contents and copy only the rules you
want to your configuration under the `services` key:

```
services:
	-
		class: PHPStan\Rules\Drupal\PluginManager\PluginManagerSetsCacheBackendRule
		tags:
			- phpstan.rules.rule
```

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
        entityTypeStorageMapping:
            node: Drupal\node\NodeStorage
            taxonomy_term: Drupal\taxonomy\TermStorage
            user: Drupal\user\UserStorage
```

To add support for custom entities, you may add the same definition in your project's `phpstan.neon`. See the following
example for adding a mapping for Search API:

```
parameters:
    drupal:
        entityTypeStorageMapping:
            search_api_index: Drupal\search_api\Entity\SearchApiConfigEntityStorage
            search_api_server: Drupal\search_api\Entity\SearchApiConfigEntityStorage
```
