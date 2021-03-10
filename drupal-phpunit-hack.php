<?php declare(strict_types=1);

/**
 * @file
 *
 * Implements PHPUnit compatibility hack provided by ClassWriter
 *
 * This ensures we can run our PHPUnit tests against 8.9 and 9, and more.
 *
 * @see \Drupal\TestTools\PhpUnitCompatibility\PhpUnit8\ClassWriter::mutateTestBase()
 */
$autoloader = null;
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    $autoloader = require __DIR__ . '/vendor/autoload.php';
}
if (!$autoloader instanceof \Composer\Autoload\ClassLoader) {
    return;
}

// Inspired by Symfony's simple-phpunit remove typehints from TestCase.
$alteredFile = $autoloader->findFile('PHPUnit\Framework\TestCase');
$phpunit_dir = dirname($alteredFile, 3);
// Mutate TestCase code to make it compatible with Drupal 8 and 9 tests.
$alteredCode = file_get_contents($alteredFile);
$alteredCode = preg_replace('/^    ((?:protected|public)(?: static)? function \w+\(\)): void/m', '    $1', $alteredCode);
$alteredCode = str_replace("__DIR__ . '/../Util/", "'$phpunit_dir/src/Util/", $alteredCode);
// Only write when necessary.
$filename = __DIR__ . '/tests/fixtures/TestCase.php';

if (!file_exists($filename) || md5_file($filename) !== md5($alteredCode)) {
    file_put_contents($filename, $alteredCode);
}
include $filename;
