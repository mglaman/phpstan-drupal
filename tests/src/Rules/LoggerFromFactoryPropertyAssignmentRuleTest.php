<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\LoggerFromFactoryPropertyAssignmentRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

final class LoggerFromFactoryPropertyAssignmentRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): Rule
    {
        return new LoggerFromFactoryPropertyAssignmentRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__ . '/data/logger-from-factory-property-assignment.php'],
            [
                [
                    'Logger assigned from LoggerChannelFactory in a class using DependencySerializationTrait will break serialization. Inject a named logger channel service directly (e.g. @logger.channel.my_channel) instead.',
                    23,
                ],
            ]
        );
    }
}
