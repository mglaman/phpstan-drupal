<?php declare(strict_types=1);

namespace PHPStan\Drupal;

final class DrushIntegrationTest extends AnalyzerTestBase
{
    /**
     * @dataProvider dataPaths
     */
    public function testPaths($path) {
        $errors = $this->runAnalyze($path);
        $this->assertCount(0, $errors->getErrors(), var_export($errors, TRUE));
        $this->assertCount(0, $errors->getInternalErrors(), var_export($errors, TRUE));
    }

    public function dataPaths(): \Generator
    {
        yield [__DIR__ . '/../fixtures/drupal/modules/drush_command/src/Commands/TestDrushCommands.php'];
    }

}
