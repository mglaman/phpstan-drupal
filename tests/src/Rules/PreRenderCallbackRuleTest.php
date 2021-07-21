<?php declare(strict_types=1);

namespace PHPStan\Drupal\Rules;

use PHPStan\Drupal\AnalyzerTestBase;

final class PreRenderCallbackRuleTest extends AnalyzerTestBase {

    /**
     * @dataProvider fileData
     */
    public function testRule(string $path, int $count, array $errorMessages): void
    {
        $errors = $this->runAnalyze($path);
        self::assertCount($count, $errors->getErrors(), var_export($errors, true));
        foreach ($errors->getErrors() as $key => $error) {
            self::assertEquals($errorMessages[$key], $error->getMessage());
        }
    }

    public function fileData(): \Generator
    {
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/phpstan_fixtures/src/RenderArrayWithPreRenderCallback.php',
            0,
            []
        ];
    }


}
