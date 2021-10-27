<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

final class DrushIntegrationTest extends AnalyzerTestBase
{
    /**
     * @dataProvider dataPaths
     */
    public function testPaths($path) {
        $errors = $this->runAnalyze($path);
        $errorMessages = [
            'Call to deprecated function drush_is_windows():
. Use \\Consolidation\\SiteProcess\\Util\\Escape.',
        ];
        $this->assertCount(1, $errors->getErrors(), var_export($errors, TRUE));
        $this->assertCount(0, $errors->getInternalErrors(), var_export($errors, TRUE));
        foreach ($errors->getErrors() as $key => $error) {
            $this->assertEquals($errorMessages[$key], $error->getMessage());
        }
        }

    public function dataPaths(): \Generator
    {
        yield [__DIR__ . '/../fixtures/drupal/modules/drush_command/src/Commands/TestDrushCommands.php'];
    }

}
