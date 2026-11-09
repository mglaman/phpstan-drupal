<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

trait RegisteredRulesTrait
{
    /**
     * @return list<class-string>
     */
    private static function registeredRuleClasses(): array
    {
        return array_map(
            static fn (object $rule): string => $rule::class,
            array_values(self::getContainer()->getServicesByTag('phpstan.rules.rule'))
        );
    }
}
