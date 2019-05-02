<?php declare(strict_types=1);

namespace PHPStan\Drupal;

final class DrushIntegrationTest extends AnalyzerTestBase
{
    /**
     * @dataProvider dataPaths
     */
    public function testPaths($path) {
        $errors = $this->runAnalyze($path);
        $this->assertCount(0, $errors, print_r($errors, true));
    }

    public function dataPaths(): \Generator
    {
        yield [__DIR__ . '/../fixtures/drupal/modules/drush_command/src/Commands/TestDrushCommands.php'];
    }

}
