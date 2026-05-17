<?php

namespace CacheBackendInterfaceStub;

use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Cache\MemoryBackend;
use function PHPStan\Testing\assertType;

$cids = ['foo'];

assert($dbBackend instanceof DatabaseBackend);
assertType('object{data: mixed, created: int, tags: array<string>, valid: bool, expire: int, checksum: string, serialized: int}|false', $dbBackend->get('foo'));
assertType('array<object{data: mixed, created: int, tags: array<string>, valid: bool, expire: int, checksum: string, serialized: int}>', $dbBackend->getMultiple($cids));

assert($memoryBackend instanceof MemoryBackend);
assertType('object{data: mixed, created: int, tags: array<string>, valid: bool, expire: int, checksum: string, serialized: int}|false', $memoryBackend->get('foo'));
assertType('array<object{data: mixed, created: int, tags: array<string>, valid: bool, expire: int, checksum: string, serialized: int}>', $memoryBackend->getMultiple($cids));
