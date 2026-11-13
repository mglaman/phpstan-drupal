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

If you don't want to use `phpstan/extension-installer`, include both files in your project's PHPStan config:

```neon
includes:
    - vendor/mglaman/phpstan-drupal/extension.neon
    - vendor/mglaman/phpstan-drupal/rules.neon
```

`extension.neon` adds Drupal's types and services. `rules.neon` adds the Drupal rules. Two of those rules also load files for the rest of the analysis: `LoadIncludes` loads the file a `ModuleHandlerInterface::loadInclude()` call includes, and `ModuleLoadInclude` does the same for `module_load_include()`. Without `rules.neon`, functions from those files are reported as not found:

```
Function locale_translation_flush_projects not found.
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

`phpstan-deprecation-rules` reports code marked with `@deprecated`. Drupal also deprecates code that has no such tag, so phpstan-drupal adds its own rules for it:

| Deprecation | Identifier |
| --- | --- |
| Getting a service that is deprecated in a `*.services.yml` file with `\Drupal::service()` | `staticServiceDeprecatedService.deprecated` |
| Getting a deprecated service from the container with `get()` | `getDeprecatedService.deprecated` |
| The `context` key in a plugin annotation | `pluginAnnotationContextDefinitions.useContextDefinitionsKey` |
| A translatable `category` in a `@FieldType` annotation | `fieldTypeCategory.translatable` |
| A plugin manager that only supports annotation discovery | `pluginManager.attributeDiscoveryMissing`, `pluginManager.attributeDiscoveryMissingDefault` |

These rules run whenever `phpstan-deprecation-rules` is loaded, and they turn off with it. They don't have `drupal.rules.*` parameters. To ignore one, use its identifier in `ignoreErrors`.

### Plugin managers without attribute discovery

Drupal 12 throws an exception for plugin managers that only support annotation discovery. Drupal 11.2 deprecated them at runtime with `@trigger_error()`. phpstan-drupal reports a `DefaultPluginManager` subclass that passes an annotation class, instead of an attribute class, as the fifth argument to `parent::__construct()`. It uses two identifiers:

- `pluginManager.attributeDiscoveryMissing`: the argument is a `::class` constant, a string, or a local variable holding one.
- `pluginManager.attributeDiscoveryMissingDefault`: the argument comes from a static property or a constructor parameter default. A subclass or caller may override the default.

To fix a reported manager, pass the attribute class as the fifth argument and keep the annotation class as the sixth. This works on Drupal 10.2 and later. The rules do nothing on Drupal versions older than 10.2, which don't have attribute discovery.

The rules only check the argument's value. They also report managers that pick the class at runtime, which work on Drupal 12:

```php
parent::__construct(
  'Plugin/Example',
  $namespaces,
  $module_handler,
  ExampleInterface::class,
  class_exists(Example::class) ? Example::class : ExampleAnnotation::class,
);
```

The same applies to a `version_compare()` branch that passes the annotation class only on older Drupal versions. Ignore these by identifier:

```neon
parameters:
    ignoreErrors:
        -
            identifier: pluginManager.attributeDiscoveryMissing
            path: src/ExamplePluginManager.php
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

#### Default rules promoted in 2.1.0

As of 2.1.0, the following rules are enabled by default. Disable any of them individually if they don't fit your project:

```neon
parameters:
    drupal:
        rules:
            # Enforces that OOP hook implementations using the Hook attribute have the
            # correct method signature for hook_form_alter, hook_form_FORM_ID_alter, etc.
            # Requires Drupal 10.3+ (Hook attribute).
            hookFormAlterRule: false

            # Flags non-abstract test classes whose names do not end with "Test".
            testClassSuffixNameRule: false

            # Flags properties that are private or read-only in classes using
            # DependencySerializationTrait, which does not support them.
            dependencySerializationTraitPropertyRule: false

            # Flags calls to AccessResult static methods (::allowed(), ::forbidden(), etc.)
            # whose argument type already makes the condition always true or always false.
            accessResultConditionRule: false

            # Flags addCacheableDependency() calls whose argument does not implement
            # CacheableDependencyInterface.
            cacheableDependencyRule: false

            # Flags logger channel objects (from LoggerChannelFactoryInterface::get()) that
            # are assigned to a property in a class using DependencySerializationTrait,
            # which cannot serialize logger channels correctly.
            loggerFromFactoryPropertyAssignmentRule: false

            # Flags direct injection of EntityStorageInterface (or a subtype) into a
            # constructor. Inject EntityTypeManagerInterface and call getStorage() instead.
            entityStorageDirectInjectionRule: false

            # Flags direct use of Symfony\Component\Yaml\Yaml::parse() on Drupal-controlled
            # YAML files, which should go through Drupal's YAML parser.
            symfonyYamlParseRule: false

            # Flags cacheability issues in entity list builder and entity operation hooks.
            entityOperationsCacheabilityRule: false
```

> [!NOTE]
> `hookRules` was renamed to `hookFormAlterRule` in 2.1.0 — update your configuration if you referenced it explicitly.

#### Legacy rules made configurable in 2.2.0

The following rules were previously always on. They remain enabled by default, but can now be disabled individually:

```neon
parameters:
    drupal:
        rules:
            # Flags calls to discouraged functions such as Devel's debugging
            # functions (dpm, dsm, kint, ...) and fnmatch.
            discouragedFunctionsRule: false

            # Flags \Drupal static calls inside classes that should use
            # dependency injection instead.
            globalDrupalDependencyInjectionRule: false

            # Flags plugin managers that do not set a cache backend.
            pluginManagerSetsCacheBackendRule: false

            # Validates module_load_include() calls and loads the include for
            # the rest of the analysis.
            moduleLoadInclude: false

            # Validates ModuleHandlerInterface::loadInclude() calls and loads
            # the include for the rest of the analysis.
            loadIncludes: false
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

All options are enabled by default.

> [!NOTE]
> Content entities are registered as PHPStan universal object crates, because `ContentEntityBase::__get()` accepts any property name. With `entityFieldsViaMagicReflection` disabled, a field access such as `$node->field_foo` is typed `mixed` instead of being reported as an undefined property.

#### Class resolver return types

`ClassResolverInterface::getInstanceFromDefinition()` and `\Drupal::classResolver()` calls are narrowed to the type of the passed class name or service ID. The return value is not guaranteed at runtime: the container is checked first, and a service definition can substitute a different class. Disable the narrowing to keep `instanceof` assertions meaningful:

```neon
parameters:
    drupal:
        classResolverReturnType: false
```

Enabled by default. When disabled, calls fall back to the declared `object` return type.

#### Bleeding-edge checks

`bleedingEdge.neon` enables hook deprecation checks against `.api.php` files and stricter service-container checking. New rules land here first before graduating to the default ruleset in a minor release. Deprecation rules don't: they run whenever `phpstan-deprecation-rules` is loaded.

```neon
includes:
    - vendor/mglaman/phpstan-drupal/bleedingEdge.neon
```

What it currently enables:

- `checkCoreDeprecatedHooksInApiFiles` — reports hook implementations deprecated in Drupal core `.api.php` files
- `checkContribDeprecatedHooksInApiFiles` — reports hook implementations deprecated in contrib module `.api.php` files
- `configGetUnknownKeyRule` — reports `Config::get()` calls with a key that isn't in the config schema. See "Config schema-based checks".
- `formApiCallbackRule` — reports Form API callbacks that fail at runtime. See "Form API callback checks".
- `lazyBuilderRule` — reports `#lazy_builder` render arrays that break the renderer's rules. See "#lazy_builder checks".

> [!NOTE]
> `checkDeprecatedHooksInApiFiles` is deprecated. Use `checkCoreDeprecatedHooksInApiFiles` and `checkContribDeprecatedHooksInApiFiles` instead.

> [!NOTE]
> `containerHasAlwaysTrue: false` graduated from bleeding edge to the default in 2.1.0. `ContainerInterface::has()` returns `bool` instead of always-`true` for known services, so conditional service guards stay meaningful. Restore the old inference with:
> ```neon
> parameters:
>     drupal:
>         bleedingEdge:
>             containerHasAlwaysTrue: true
> ```

#### Config schema-based checks (experimental)

Two opt-in features use Drupal's config schema files to analyze `Config::get()` calls. Both only act on config objects whose schema has the `FullyValidatable` constraint, since only those schemas are guaranteed complete. Schema files are parsed lazily on first use, so there is no cost when the features are disabled.

```neon
parameters:
    drupal:
        # Narrows Config::get() return types from the config schema, e.g.
        # \Drupal::config('system.cron')->get('logging') resolves to bool|null
        # instead of mixed. Types are nullable because any key can be absent
        # at runtime.
        configGetReturnType: true
        rules:
            # Reports Config::get() calls with a key that does not exist in the
            # config schema, catching typos at analysis time.
            configGetUnknownKeyRule: true
```

Recognized call patterns:

- `\Drupal::config('...')->get('...')`
- `$configFactory->get('...')->get('...')`
- `$configFactory->getEditable('...')->get('...')`
- `$this->config('...')->get('...')` in classes using `ConfigFormBaseTrait`

The config name and key must be literal strings. Config entity schemas with wildcard names (e.g. `block.block.*`) are not supported yet, and keys beneath dynamic type references (e.g. `mailer_dsn.options.[%parent.scheme]`) are not validated.

Neither feature is included in `bleedingEdge.neon` yet.

#### Form API callback checks

`formApiCallbackRule` reports Form API callbacks that fail when Drupal calls them. It checks `#validate`, `#submit`, `#element_validate`, `#process`, `#after_build` and `#ajax` → `callback`, both in array literals and in assignments such as `$form['#validate'][] = 'my_validate';`. It is opt-in and enabled by `bleedingEdge.neon`.

```neon
parameters:
    drupal:
        rules:
            formApiCallbackRule: true
```

It reports:

- A function or method that does not exist (`formApiCallback.notFound`). On Drupal 11.3 and later, this includes a class or service name whose class has no public `__invoke()` method.
- A method that is not public (`formApiCallback.nonPublicMethod`).
- On Drupal 11.3 and later, a private service (`formApiCallback.privateService`). The container only returns public services.
- On Drupal 11.3 and later, an abstract class or interface named directly, which Drupal can't create an object of (`formApiCallback.notInstantiable`).
- `[Foo::class, 'method']` or `[static::class, 'method']` for a non-static method (`formApiCallback.nonStaticMethod`). Use `'::method'` in a form class, or make the method static.
- Before Drupal 11.3 only: `'Foo::method'` for a non-static method (`formApiCallback.nonStaticMethod`), and `'service:method'` strings, including `static::class . ':method'` (`formApiCallback.serviceNotation`). Drupal 11.3 and later resolve both through `CallableResolver`.

`'::method'` is only checked in concrete classes implementing `FormInterface`, outside `#[Hook]` methods: in a form alter, `'::method'` refers to the altered form's object. Callbacks that can't be resolved statically are not reported. `#value_callback` is not checked, because Drupal falls back to the default value callback instead of failing.

For a service, the rule checks the class in its definition. Decorated services are not checked, because the container returns the outermost decorator. A service built by a factory can return a subclass that has `__invoke()`, which the rule can't see. Ignore that report by identifier:

```neon
parameters:
    ignoreErrors:
        -
            identifier: formApiCallback.notFound
            path: src/Form/MyForm.php
```

#### #lazy_builder checks

`lazyBuilderRule` reports `#lazy_builder` render arrays that break the rules in `Renderer::doRender()`. It is opt-in and enabled by `bleedingEdge.neon`.

```neon
parameters:
    drupal:
        rules:
            lazyBuilderRule: true
```

It reports:

- `#create_placeholder` set to `TRUE` without `#lazy_builder` (`lazyBuilder.placeholderWithoutCallback`). Drupal throws a `LogicException` when it renders the element.
- A `#lazy_builder` array literal that isn't `[callback, arguments]`, or whose arguments aren't an array (`lazyBuilder.invalidStructure`). A `#lazy_builder` value that isn't an array literal is reported by the always-on `RenderCallbackRule` instead.
- An argument that isn't a scalar or `NULL` (`lazyBuilder.nonScalarArgument`).
- Properties or children other than `#cache`, `#create_placeholder`, `#lazy_builder_preview`, `#placeholder_strategy_denylist`, `#preview` and `#weight` next to `#lazy_builder` (`lazyBuilder.unsupportedProperty`).
- A callback method whose return type can't be an array (`lazyBuilder.nonArrayReturn`).
- Fewer arguments than the callback method requires, or more than it accepts when it isn't variadic (`lazyBuilder.argumentCount`).
- An argument that the callback method's native parameter type doesn't accept (`lazyBuilder.argumentType`). Drupal calls the callback without `strict_types`, so an argument PHP can coerce, like `'5'` for an `int` parameter, isn't reported.

Drupal passes the arguments by position, after `array_values()`, so string keys in the arguments array don't name parameters.

The first one fails on every request. So do too few arguments and arguments of the wrong type: Drupal throws an `ArgumentCountError` or a `TypeError` when it renders the placeholder. PHP ignores extra arguments. The others fail `assert()` calls, so they only throw with assertions on. Without assertions, Drupal renders the element wrong.

Only array literals are checked. An element built across several statements, such as `$build['#lazy_builder'] = ...` followed by `$build['#title'] = ...`, is not.

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

### Custom PHPDoc types

phpstan-drupal provides custom PHPDoc types that can be used to improve type safety in Drupal code.

#### `entity-type-id`

The `entity-type-id` type represents a valid Drupal entity type ID string (e.g. `'node'`, `'user'`, `'taxonomy_term'`). PHPStan will report an error when a constant string that is not a known entity type ID is passed where `entity-type-id` is expected.

Drupal coding standards require keeping PHPStan-specific types in `@phpstan-param` and `@phpstan-return` tags rather than in the standard `@param` and `@return` tags:

```php
/**
 * Loads an entity by its entity type ID and entity ID.
 *
 * @param string $entityTypeId
 *   The entity type ID.
 * @param int|string $id
 *   The entity ID.
 *
 * @phpstan-param entity-type-id $entityTypeId
 */
public function loadEntity(string $entityTypeId, int|string $id): ?EntityInterface {
    return $this->entityTypeManager->getStorage($entityTypeId)->load($id);
}
```

For return types:

```php
/**
 * Returns the entity type ID.
 *
 * @return string
 *   The entity type ID.
 *
 * @phpstan-return entity-type-id
 */
public function getEntityTypeId(): string {
    return $this->entityTypeId;
}
```

Known entity type IDs are sourced from the `drupal.entityMapping` parameter. See [Entity storage mappings](#entity-storage-mappings) for how to register custom entity types so their IDs are also recognized.

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
