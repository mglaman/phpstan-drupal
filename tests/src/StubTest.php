<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests;

use PHPStan\Analyser\Error;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\PhpDoc\StubValidator;
use PHPStan\Testing\PHPStanTestCase;

final class StubTest extends PHPStanTestCase
{
    use AdditionalConfigFilesTrait;

    public function testValid(): void {
        $stubFilesProvider = self::getContainer()
            ->getByType(StubFilesProvider::class);
        // @phpstan-ignore-next-line
        $projectStubFiles = $stubFilesProvider->getProjectStubFiles();

        $stubValidators = self::getContainer()
            ->getByType(StubValidator::class);
        // @phpstan-ignore-next-line
        $stubErrors = $stubValidators->validate($projectStubFiles, true);
        $errorsAsArrays = array_map(
            static fn (Error $error) => $error->jsonSerialize(),
            $stubErrors
        );
        self::assertEquals(
            [],
            $errorsAsArrays,
            var_export($errorsAsArrays, true)
        );
    }

}
