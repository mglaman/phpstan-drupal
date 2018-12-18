# phpstan-drupal

Extension for PHPStan to allow analysis of Drupal code.

## Usage

Add `phpstan.neon` to your Drupal project.

Make sure it has

```neon
includes:
	- vendor/mglaman/phpstan-drupal/extension.neon
```

## Enabling rules one-by-one

If you don't want to start using all the available strict rules at once but only one or two, you can! Just don't include the whole `rules.neon` from this package in your configuration, but look at its contents and copy only the rules you want to your configuration under the `services` key:

```
services:
	-
		class: PHPStan\Rules\Drupal\PluginManager\PluginManagerSetsCacheBackendRule
		tags:
			- phpstan.rules.rule
```
