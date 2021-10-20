<?php declare(strict_types=1);

namespace PHPStan\Drupal\Rules;

use PHPStan\Drupal\AnalyzerTestBase;

final class PreRenderCallbackRuleTest extends AnalyzerTestBase {

    /**
     * @dataProvider fileData
     */
    public function testRule(string $path, array $errorMessages): void
    {
        $errors = $this->runAnalyze($path);
        self::assertCount(count($errorMessages), $errors->getErrors(), var_export($errors, true));
        foreach ($errors->getErrors() as $key => $error) {
            self::assertEquals($errorMessages[$key], $error->getMessage());
        }
    }

    public function fileData(): \Generator
    {
        yield [
          __DIR__ . '/../../fixtures/drupal/modules/pre_render_callback_rule/pre_render_callback_rule.module',
            [
                "#pre_render value 'null' at key '0' is invalid.",
                'The "#pre_render" render array value expects an array of callbacks.',
                'The "#pre_render" render array value expects an array of callbacks.',
                "#pre_render callback 'invalid_func' at key '0' is not callable.",
                "#pre_render callback 'sample_pre_render…' at key '1' is not trusted. See https://www.drupal.org/node/2966725.",
                "#pre_render callback class '\Drupal\pre_render_callback_rule\NotTrustedCallback' at key '3' does not implement Drupal\Core\Security\TrustedCallbackInterface.",
            ]
        ];
        yield [
            __DIR__ . '/../../fixtures/drupal/modules/pre_render_callback_rule/src/RenderArrayWithPreRenderCallback.php',
            []
        ];
    }


}
