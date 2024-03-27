# phpstan-drupal

[![Tests](https://github.com/mglaman/phpstan-drupal/actions/workflows/php.yml/badge.svg)](https://github.com/mglaman/phpstan-drupal/actions/workflows/php.yml) [![CircleCI](https://circleci.com/gh/mglaman/phpstan-drupal.svg?style=svg)](https://circleci.com/gh/mglaman/phpstan-drupal)

Extension for [PHPStan](https://phpstan.org/) to allow analysis of Drupal code.

PHPStan is able to [discover symbols](https://phpstan.org/user-guide/discovering-symbols) by using autoloading provided 
by Composer. However, Drupal does not provide autoloading information for modules and themes. This project registers 
those namespaces so that PHPStan can properly discover symbols in your Drupal code base automatically.

## Sponsors

<a href="https://www.undpaul.de/"><img src="https://www.undpaul.de/themes/custom/undpaul3/logo.svg" alt="undpaul" width="250" /></a>
<a href="https://www.optasy.com/"><svg id="Warstwa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="200" viewBox="0 0 160 60" enable-background="new 0 0 160 60" xml:space="preserve"><g> <path fill="#3A3A38" d="M38.761,49.624c-0.935,0-1.741-0.761-1.741-1.73v-23.75c0-0.97,0.695-1.729,1.63-1.729h10.283	c0.138,0,0.969-0.002,2.042,0.275c1.523,0.381,2.874,1.143,3.912,2.146c0.763,0.762,1.386,1.696,1.801,2.769	c0.45,1.178,0.692,2.528,0.692,4.018c0,3.288-1.385,5.781-3.981,7.271c-1.87,1.073-4.362,1.593-7.444,1.593	c-0.935,0-1.73-0.763-1.73-1.73c0-0.971,0.796-1.731,1.73-1.731c2.459,0,4.397-0.382,5.713-1.144	c1.523-0.866,2.251-2.216,2.251-4.258c0-1.386-0.277-2.528-0.762-3.395c-0.381-0.692-0.935-1.2-1.593-1.581	c-1.246-0.727-2.527-0.749-2.631-0.749h-8.43v21.996C40.503,48.863,39.73,49.624,38.761,49.624 M83.187,24.155	c0-0.969-0.763-1.741-1.73-1.741H62.794c-0.97,0-1.731,0.772-1.731,1.741c0,0.97,0.762,1.742,1.731,1.742h7.563v21.996	c0,0.97,0.772,1.73,1.742,1.73c0.936,0,1.742-0.761,1.742-1.73V25.897h7.614C82.424,25.897,83.187,25.125,83.187,24.155 M106.969,49.487c0.865-0.415,1.246-1.421,0.865-2.319L97.065,23.416c-0.242-0.589-0.899-1.005-1.558-1.005	c-0.692,0-1.281,0.382-1.593,1.005L82.976,47.168c-0.347,0.796-0.451,1.765,0.311,2.249c0.728,0.451,1.593,0.242,2.251-0.415	c1.315-1.28,3.115-2.7,4.847-3.773c1.696-1.039,3.428-1.697,5.193-2.044c2.182-0.484,4.432-0.45,6.682,0.104l2.39,5.332	C105.133,49.557,106.171,49.833,106.969,49.487 M100.701,39.792c-1.904-0.173-3.809-0.103-5.678,0.312	c-1.939,0.382-4.362,1.315-6.198,2.354l6.648-14.161L100.701,39.792z M122.499,50.179c2.284,0,8.378-1.073,9.417-6.232	c0.692-3.461-0.277-7.547-7.687-9.348c-5.781-1.419-8.31-2.285-8.31-5.193c0-3.15,3.082-4.188,5.782-4.188	c1.144,0,2.319,0.033,3.393,0.449c1.281,0.484,2.32,1.212,3.014,1.973c0.172,0.139,0.311,0.313,0.483,0.416	c0.623,0.346,1.386,0.104,1.801-0.416c0.381-0.449,0.52-1.073,0.312-1.627c-0.449-1.314-1.939-2.562-3.844-3.288	c-1.488-0.589-3.357-0.9-5.158-0.9c-2.389,0-6.474,0.484-8.586,4.708c-0.692,1.42-0.901,4.432,0.242,6.336	c0.935,1.593,2.596,2.597,4.259,3.255c1.558,0.658,3.219,1.074,4.882,1.421c2.492,0.519,5.436,1.385,6.231,3.738	c0.277,0.796,0.208,1.695-0.104,2.493c-0.623,1.592-2.181,2.423-3.809,2.77c-1.315,0.277-2.942,0.312-4.536,0.069	c-1.869-0.312-3.809-0.97-5.054-2.493c-0.208-0.277-0.416-0.589-0.728-0.762c-1.246-0.624-2.527,0.9-2.112,2.112	c0.415,1.178,1.42,2.251,2.943,3.047c1.211,0.692,2.77,1.177,4.432,1.453C120.663,50.11,121.599,50.179,122.499,50.179 M157.722,25.112c0.52-0.797,0.291-1.869-0.506-2.424c-0.796-0.519-1.891-0.312-2.41,0.485l-7.891,11.806l-7.855-11.806	c-0.52-0.797-1.584-1.004-2.381-0.485c-0.797,0.555-0.987,1.627-0.468,2.424l9.035,13.504v9.277c0,0.935,0.807,1.73,1.741,1.73	c0.97,0,1.741-0.796,1.741-1.73v-9.277L157.722,25.112"></path> <linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="16.1777" y1="50.1787" x2="16.1777" y2="9.8218"> <stop offset="0" style="stop-color:#6B4795"></stop> <stop offset="0.0384" style="stop-color:#654D99"></stop> <stop offset="0.3296" style="stop-color:#3E73B7"></stop> <stop offset="0.5966" style="stop-color:#218ECD"></stop> <stop offset="0.8288" style="stop-color:#109FDA"></stop> <stop offset="1" style="stop-color:#0AA5DF"></stop> </linearGradient> <path fill="url(#SVGID_1_)" d="M16.494,10.172l-0.316-0.351l-0.315,0.351c-0.348,0.386-5.489,6.143-9.41,12.917l9.726-7.164	c4.209,5.299,10.233,14.174,10.233,20.076c0,5.644-4.59,10.233-10.233,10.233c-5.643,0-10.233-4.59-10.233-10.233	c0-5.643,4.591-10.233,10.233-10.233c1.089,0,1.972-0.883,1.972-1.973c0-1.089-0.883-1.972-1.972-1.972	C8.348,21.823,2,28.171,2,36.001s6.348,14.178,14.178,14.178c7.818,0,14.178-6.36,14.178-14.178	C30.355,25.68,17.06,10.8,16.494,10.172z"></path></g></svg></a>
<a href="https://www.fame.fi/"><img src="https://www.fame.fi/assets/images/fame-logo.png" alt="Fame"></a>

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
