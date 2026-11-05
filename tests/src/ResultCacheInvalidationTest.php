<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Runs PHPStan's real result cache against a Drupal site that changes.
 *
 * PHPStan decides whether a result is still valid in the main process, before
 * bootstrap files run, and the analysis happens in worker processes after they
 * ran. Only a real run covers that difference, so this doesn't use an in-process
 * test harness.
 */
final class ResultCacheInvalidationTest extends TestCase
{

    private string $projectRoot;

    private string $moduleDirectory;

    private string $otherModuleDirectory;

    private string $workDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $projectRoot = \realpath(__DIR__ . '/../..');
        self::assertNotFalse($projectRoot);
        $this->projectRoot = $projectRoot;
        $this->moduleDirectory = $projectRoot . '/tests/fixtures/drupal/modules/result_cache_test';
        $this->otherModuleDirectory = $projectRoot . '/tests/fixtures/drupal/modules/result_cache_other';
        $this->workDirectory = \sys_get_temp_dir() . '/phpstan_drupal_result_cache_' . \uniqid();

        \mkdir($this->workDirectory . '/src', 0777, true);
        \mkdir($this->moduleDirectory . '/src', 0777, true);
        \mkdir($this->moduleDirectory . '/config/schema', 0777, true);

        \file_put_contents($this->workDirectory . '/phpstan.neon', <<<NEON
includes:
	- {$projectRoot}/extension.neon
	- {$projectRoot}/rules.neon
parameters:
	level: 0
	tmpDir: {$this->workDirectory}/tmp
	paths:
		- {$this->workDirectory}/src
	parallel:
		maximumNumberOfProcesses: 2
	drupal:
		rules:
			configGetUnknownKeyRule: true
NEON);
        $this->writeSource('service_foo', "\\Drupal::service('result_cache_test.foo');");
        $this->writeSource('service_bar', "\\Drupal::service('result_cache_test.bar');");
        $this->writeSource('unrelated', 'echo 1;');
        $this->writeSource('other_module', 'new \\Drupal\\result_cache_other\\Thing();');
        $this->writeSource('config', "\\Drupal::config('result_cache_test.settings')->get('known');");
        $this->writeSource('include', "\\Drupal::moduleHandler()->loadInclude('result_cache_test', 'inc', 'result_cache_test.extra');");

        \file_put_contents($this->moduleDirectory . '/result_cache_test.info.yml', "name: Test\ntype: module\n");
        \file_put_contents($this->moduleDirectory . '/src/Foo.php', "<?php\nnamespace Drupal\\result_cache_test;\nclass Foo {}\n");
        $this->writeServices('');
        $this->writeSchema('known');
    }

    protected function tearDown(): void
    {
        $this->remove($this->moduleDirectory);
        $this->remove($this->otherModuleDirectory);
        $this->remove($this->workDirectory);
        parent::tearDown();
    }

    public function testResultCacheInvalidation(): void
    {
        $output = $this->analyse();
        self::assertStringContainsString('Result cache not used', $output);
        self::assertStringContainsString('Thing not found', $output);
        self::assertStringContainsString('result_cache_test.extra.inc could not be loaded', $output);

        self::assertSame(0, $this->reanalysedFiles($this->analyse()), 'Nothing changed.');

        \file_put_contents($this->moduleDirectory . '/result_cache_test.info.yml', "name: Edited\ntype: module\n");
        self::assertSame(0, $this->reanalysedFiles($this->analyse()), 'The info file content does not affect the analysis.');

        $this->writeServices("  result_cache_test.unrelated:\n    class: Drupal\\result_cache_test\\Foo\n");
        self::assertSame(0, $this->reanalysedFiles($this->analyse()), 'Nothing asks about the new service.');

        $this->writeServices("", "    deprecated: 'Bar is deprecated.'\n");
        $output = $this->analyse();
        self::assertSame(1, $this->reanalysedFiles($output), 'Only one file asks about the changed service.');
        self::assertStringContainsString('Bar is deprecated.', $output);

        $this->writeSchema('renamed');
        $output = $this->analyse();
        self::assertSame(1, $this->reanalysedFiles($output), 'Only one file reads config schema.');
        self::assertStringContainsString('Config key "known" does not exist', $output);

        \file_put_contents($this->moduleDirectory . '/result_cache_test.extra.inc', "<?php\n");
        $output = $this->analyse();
        self::assertSame(1, $this->reanalysedFiles($output), 'Only one file loads the created include.');
        self::assertStringNotContainsString('result_cache_test.extra.inc could not be loaded', $output);

        \mkdir($this->otherModuleDirectory . '/src', 0777, true);
        \file_put_contents($this->otherModuleDirectory . '/result_cache_other.info.yml', "name: Other\ntype: module\n");
        \file_put_contents($this->otherModuleDirectory . '/src/Thing.php', "<?php\nnamespace Drupal\\result_cache_other;\nclass Thing {}\n");
        $output = $this->analyse();
        self::assertSame(6, $this->reanalysedFiles($output), 'A new module changes which classes exist.');
        self::assertStringNotContainsString('Thing not found', $output);

        self::assertSame(0, $this->reanalysedFiles($this->analyse()), 'Nothing changed after adding the module.');

        $this->remove($this->otherModuleDirectory);
        $output = $this->analyse();
        self::assertSame(6, $this->reanalysedFiles($output), 'A removed module changes which classes exist.');
        self::assertStringContainsString('Thing not found', $output);
    }

    private function writeSource(string $name, string $statement): void
    {
        \file_put_contents("{$this->workDirectory}/src/$name.php", "<?php\nfunction result_cache_$name(): void { $statement }\n");
    }

    private function writeServices(string $extra, string $barExtra = ''): void
    {
        \file_put_contents($this->moduleDirectory . '/result_cache_test.services.yml', <<<YAML
services:
  result_cache_test.foo:
    class: Drupal\\result_cache_test\\Foo
  result_cache_test.bar:
    class: Drupal\\result_cache_test\\Foo

YAML . $barExtra . $extra);
    }

    private function writeSchema(string $key): void
    {
        \file_put_contents($this->moduleDirectory . '/config/schema/result_cache_test.schema.yml', <<<YAML
result_cache_test.settings:
  type: config_object
  constraints:
    FullyValidatable: ~
  mapping:
    $key:
      type: string

YAML);
    }

    private function analyse(): string
    {
        $command = \sprintf(
            'cd %s && %s vendor/bin/phpstan analyse -c %s --no-progress --error-format=raw -vvv 2>&1',
            \escapeshellarg($this->projectRoot),
            \escapeshellarg(PHP_BINARY),
            \escapeshellarg($this->workDirectory . '/phpstan.neon')
        );
        \exec($command, $lines);
        return \implode("\n", $lines);
    }

    private function reanalysedFiles(string $output): int
    {
        if (\preg_match('/Result cache restored(?: in [\d.]+ seconds)?\. (\d+) files? will be reanalysed/', $output, $matches) !== 1) {
            self::fail($output);
        }
        return (int) $matches[1];
    }

    private function remove(string $path): void
    {
        if (!\file_exists($path)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? \rmdir($file->getPathname()) : \unlink($file->getPathname());
        }
        \rmdir($path);
    }
}
