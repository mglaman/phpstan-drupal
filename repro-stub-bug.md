# PHPStan 2.1.38+ Stub File Regression Reproduction

## The Bug

Stub file `@param` type overrides are no longer applied inside method bodies
when analyzing the source file. They still work for external callers
(method reflection), but the method's own scope uses the source file's
phpDoc types instead of the stub's types.

## Local Reproduction

Create these three files:

### `phpstan.neon`

```neon
parameters:
    level: 8
    paths:
        - src
    stubFiles:
        - stubs/Foo.stub
```

### `src/Foo.php`

```php
<?php
declare(strict_types=1);

class Foo
{
    /**
     * Source says @param string, but stub says @param callable-string
     * @param string $callback
     */
    public function doSomething($callback): void
    {
        // BUG: Shows "string" since 2.1.38. Should show "callable-string" from stub.
        \PHPStan\dumpType($callback);
    }
}

class Bar
{
    public function externalCaller(Foo $foo): void
    {
        // Stub DOES work for external callers (method reflection).
        // 'strlen' is a callable-string, so this passes level 8 with no error.
        $foo->doSomething('strlen');

        // Passing a non-callable string errors, proving stub IS applied to reflection:
        $foo->doSomething('not_a_function');
    }
}
```

### `stubs/Foo.stub`

```php
<?php

class Foo
{
    /**
     * @param callable-string $callback
     */
    public function doSomething($callback): void
    {
    }
}
```

### Run

```bash
vendor/bin/phpstan analyse
```

### Output (2.1.38+)

```
 ------ -------------------------------------------------------------
  Line   Foo.php
 ------ -------------------------------------------------------------
  13     Dumped type: string
  26     Parameter #1 $callback of method Foo::doSomething() expects
         callable-string, 'not_a_function' given.
 ------ -------------------------------------------------------------
```

Line 13 proves the stub is **NOT** applied inside the method body (`string` instead of `callable-string`).
Line 26 proves the stub **IS** applied for external callers (`expects callable-string`).

### Expected output (2.1.37)

```
 ------ -------------------------------------------------------------
  Line   Foo.php
 ------ -------------------------------------------------------------
  13     Dumped type: callable-string
  26     Parameter #1 $callback of method Foo::doSomething() expects
         callable-string, 'not_a_function' given.
 ------ -------------------------------------------------------------
```

## Root Cause

PR phpstan/phpstan-src#4829 ("Rework phpDoc inheritance to resolve through
reflection instead of re-walking the hierarchy") rewrote
`PhpDocInheritanceResolver::resolvePhpDocForMethod()` and removed the
`StubPhpDocProvider` consultation that previously existed in
`docBlockToResolvedDocBlock()`.

### Old code path (2.1.37)

1. `NodeScopeResolver::getPhpDocs()` calls `PhpDocInheritanceResolver::resolvePhpDocForMethod()`
2. Which calls `PhpDocBlock::resolvePhpDocBlockForMethod()` → `docBlockToResolvedDocBlock()`
3. `docBlockToResolvedDocBlock()` calls `StubPhpDocProvider::findMethodPhpDoc()` — if a stub exists, it returns the stub's types
4. Falls back to `FileTypeMapper::getResolvedPhpDoc()` only if no stub found

### New code path (2.1.38+)

1. `NodeScopeResolver::getPhpDocs()` resolves `$currentResolvedPhpDoc` directly from the source file via `FileTypeMapper::getResolvedPhpDoc()` — **stubs are never consulted**
2. Passes it to the new `PhpDocInheritanceResolver::resolvePhpDocForMethod()` which only merges parent method phpDoc
3. Neither `NodeScopeResolver` nor the new `PhpDocInheritanceResolver` have `StubPhpDocProvider` injected

### Where stubs still work

Stubs ARE still correctly applied in `PhpClassReflectionExtension::createUserlandMethodReflection()`
via `findMethodPhpDocIncludingAncestors()`, which is why external callers
see the stub types. But `NodeScopeResolver::getPhpDocs()` (used for method
body scope) bypasses this entirely.

---

## Fix for phpstan-src

### Files to change

#### 1. `src/Analyser/NodeScopeResolver.php`

**Add import:**

```php
use PHPStan\PhpDoc\StubPhpDocProvider;
```

**Add property** (after `private ReflectionProvider $reflectionProvider;`):

```php
private StubPhpDocProvider $stubPhpDocProvider;
```

**Add constructor parameter** (after `InitializerExprTypeResolver $initializerExprTypeResolver,`):

```php
StubPhpDocProvider $stubPhpDocProvider,
```

**Add constructor assignment** (after `$this->initializerExprTypeResolver = $initializerExprTypeResolver;`):

```php
$this->stubPhpDocProvider = $stubPhpDocProvider;
```

**Fix `getPhpDocs()` method** — replace the `ClassMethod` phpDoc resolution block:

```php
// BEFORE (broken):
$currentResolvedPhpDoc = null;
if ($docComment !== null) {
    $currentResolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
        $file, $class, $trait, $node->name->name, $docComment
    );
}

// AFTER (fixed):
$currentResolvedPhpDoc = null;
if ($class !== null) {
    $currentResolvedPhpDoc = $this->stubPhpDocProvider->findMethodPhpDoc(
        $class, $class, $functionName, $positionalParameterNames
    );
}
if ($currentResolvedPhpDoc === null && $docComment !== null) {
    $currentResolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
        $file, $class, $trait, $functionName, $docComment
    );
}
```

### Test files to add

#### 2. `tests/PHPStan/Analyser/data/stub-method-body-types.stub`

```php
<?php

class StubMethodBodyTypes
{
    /**
     * @param callable-string $callback
     * @return non-empty-string
     */
    public function process($callback): string
    {
    }
}
```

#### 3. `tests/PHPStan/Analyser/data/stub-method-body-types.php`

```php
<?php

namespace StubMethodBodyTypes;

use function PHPStan\Testing\assertType;

class StubMethodBodyTypes
{
    /**
     * Source declares @param string — stub overrides to @param callable-string
     * @param string $callback
     * @return string
     */
    public function process($callback): string
    {
        // Stub should override the source file's @param type inside the method body
        assertType('callable-string', $callback);

        return $callback;
    }
}

class ExternalCaller
{
    public function test(StubMethodBodyTypes $obj): void
    {
        // Stub should also work for external callers (this already works)
        assertType('non-empty-string', $obj->process('strlen'));
    }
}
```

#### 4. `tests/PHPStan/Analyser/data/stub-method-body-types.neon`

```neon
parameters:
    stubFiles:
        - stub-method-body-types.stub
```

#### 5. `tests/PHPStan/Analyser/StubMethodBodyTypesTest.php`

```php
<?php declare(strict_types=1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class StubMethodBodyTypesTest extends TypeInferenceTestCase
{

    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/stub-method-body-types.php');
    }

    /**
     * @dataProvider dataFileAsserts
     */
    public function testFileAsserts(
        string $assertType,
        string $file,
        mixed ...$args,
    ): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/data/stub-method-body-types.neon',
        ];
    }

}
```

### Why this works

The fix restores the behavior from 2.1.37 where `StubPhpDocProvider` is consulted
before falling back to the source file's doc comment. The check is placed before
the `fileTypeMapper` call, so stubs take priority (matching the old
`docBlockToResolvedDocBlock()` behavior). If no stub exists for the method,
it falls through to the original source file resolution as before.

The `$class !== null` guard ensures we only look up stubs when inside a class
(which is always true for ClassMethod nodes, but the guard is defensive).

`StubPhpDocProvider` is already `#[AutowiredService]` so adding it to the
constructor is all that's needed for dependency injection — no neon config changes
required.
