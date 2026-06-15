<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\ConfigGetUnknownKeyRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class ConfigGetUnknownKeyRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return $this->getContainer()->getByType(ConfigGetUnknownKeyRule::class);
    }

    /**
     * @dataProvider resultData
     *
     * @param list<array{0: string, 1: int, 2?: string|null}> $errorMessages
     */
    public function testRule(string $path, array $errorMessages): void
    {
        $this->analyse([$path], $errorMessages);
    }

    public static function resultData(): \Generator
    {
        yield 'unknown keys on FullyValidatable configs' => [
            __DIR__ . '/data/config-get-unknown-key.php',
            [
                [
                    'Config key "nonexistent_key" does not exist in the schema for "system.maintenance".',
                    14,
                ],
                [
                    'Config key "threshold.unknown_key" does not exist in the schema for "system.cron".',
                    20,
                ],
                [
                    'Config key "totally_wrong" does not exist in the schema for "system.cron".',
                    23,
                ],
                [
                    'Config key "bad_key" does not exist in the schema for "system.maintenance".',
                    31,
                ],
                [
                    'Config key "bad_editable_key" does not exist in the schema for "system.maintenance".',
                    37,
                ],
                [
                    'Config key "unknown_via_trait" does not exist in the schema for "system.maintenance".',
                    64,
                ],
            ],
        ];
    }
}
