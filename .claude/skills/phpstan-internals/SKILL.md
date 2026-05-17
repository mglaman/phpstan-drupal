---
name: phpstan-internals
description: Navigate and read PHPStan's internal source code from the phar file
user-invocable: false
---

# PHPStan Internals Navigation

PHPStan is distributed as a phar file. Claude's normal file tools (Read, Grep, Glob) cannot access phar contents. Use the methods below to explore PHPStan's internal APIs when developing rules and type extensions.

## Phar Location

- **File**: `vendor/phpstan/phpstan/phpstan.phar`
- **Phar path prefix**: `phar:///Users/mglaman/Projects/php/phpstan-drupal/vendor/phpstan/phpstan/phpstan.phar/`

## Method 1 — JetBrains MCP (preferred)

Use the JetBrains MCP `get_symbol_info` tool to look up PHPStan classes and interfaces. It resolves symbols via PhpStorm's index and returns declarations and documentation.

Find a use statement referencing the PHPStan class in any `src/` file, then call `get_symbol_info` with the fully qualified class name.

## Method 2 — PHP CLI (fallback)

When JetBrains MCP is unavailable or you need to browse directories, use PHP CLI commands via Bash.

### Read a file

```bash
php -r "echo file_get_contents('phar:///Users/mglaman/Projects/php/phpstan-drupal/vendor/phpstan/phpstan/phpstan.phar/src/Type/DynamicMethodReturnTypeExtension.php');"
```

### List a directory

```bash
php -r "print_r(scandir('phar:///Users/mglaman/Projects/php/phpstan-drupal/vendor/phpstan/phpstan/phpstan.phar/src/Type/'));"
```

### Search within a file

```bash
php -r "echo file_get_contents('phar:///Users/mglaman/Projects/php/phpstan-drupal/vendor/phpstan/phpstan/phpstan.phar/src/Type/DynamicMethodReturnTypeExtension.php');" | grep -n 'getTypeFromMethodCall'
```

### Search across multiple files

```bash
php -r "\$dir = 'phar:///Users/mglaman/Projects/php/phpstan-drupal/vendor/phpstan/phpstan/phpstan.phar/src/Type/'; foreach (scandir(\$dir) as \$f) { if (substr(\$f, -4) === '.php') { \$c = file_get_contents(\$dir . \$f); if (strpos(\$c, 'SearchTerm') !== false) echo \$f . PHP_EOL; }}"
```

## Key Directories Inside the Phar

| Directory | Contents |
|---|---|
| `src/Analyser/` | Scope, NodeScopeResolver, analysis engine |
| `src/Type/` | Type system, type extensions interfaces |
| `src/Rules/` | Built-in rules, Rule interface |
| `src/Reflection/` | ReflectionProvider, class/method/property reflection |
| `src/PhpDoc/` | PHPDoc parsing, type resolving |
| `src/DependencyInjection/` | Container, extension loading |
| `src/Testing/` | RuleTestCase, TypeInferenceTestCase base classes |
| `src/Node/` | Virtual nodes (InClassNode, InClassMethodNode, etc.) |

## Commonly Extended Interfaces

These are the PHPStan interfaces and classes that phpstan-drupal most frequently extends:

- `PHPStan\Rules\Rule` — Custom analysis rules
- `PHPStan\Type\DynamicMethodReturnTypeExtension` — Dynamic return types for method calls
- `PHPStan\Type\DynamicStaticMethodReturnTypeExtension` — Dynamic return types for static method calls
- `PHPStan\Type\DynamicFunctionReturnTypeExtension` — Dynamic return types for function calls
- `PHPStan\Analyser\Scope` — Analysis scope (variable types, context)
- `PHPStan\Reflection\ReflectionProvider` — Look up class/function reflections
- `PHPStan\Reflection\MethodReflection` — Method reflection data
- `PHPStan\Type\Type` — Base type interface
- `PHPStan\Type\ObjectType` — Represents a class instance type
- `PHPStan\Testing\RuleTestCase` — Base class for rule tests
