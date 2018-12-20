# phpstan-drupal

[![Build Status](https://travis-ci.com/mglaman/phpstan-drupal.svg?branch=master)](https://travis-ci.com/mglaman/phpstan-drupal)

Extension for PHPStan to allow analysis of Drupal code.

## Usage

Add `phpstan.neon` to your Drupal project.

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
	excludes_analyse:
		- *Test.php
		- *TestBase.php
```

## Adapting to your project

### Entity storage mappings.

The `EntityTypeManagerGetStorageDynamicReturnTypeExtension` service helps map dynamic return types. This inspects the 
passed entity type ID and tries to return a known storage class, besides the default `EntityStorageInterface`. The
default mapping can be found in `extension.neon`. For example:

```
drupal:
	entityTypeStorageMapping:
		node: Drupal\node\NodeStorage
		taxonomy_term: Drupal\taxonomy\TermStorage
		user: Drupal\user\UserStorage
```

To add support for custom entities, you may add the same definition in your project's `phpstan.neon`. See the following
example for adding a mapping for Search API:

```
drupal:
	entityTypeStorageMapping:
		search_api_index: Drupal\search_api\Entity\SearchApiConfigEntityStorage
		search_api_server: Drupal\search_api\Entity\SearchApiConfigEntityStorage
```
