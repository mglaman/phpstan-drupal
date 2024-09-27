<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;

/**
 * A PHP baseline error formatter that ignores by identifier instead of message.
 */
final class IdentifierIgnoreErrorFormatter {

    public function __construct(private RelativePathHelper $relativePathHelper) {}

    public function formatErrors(
        AnalysisResult $analysisResult,
        Output $output,
    ): int {
        if (!$analysisResult->hasErrors()) {
            $php = '<?php declare(strict_types = 1);';
            $php .= "\n\n";
            $php .= 'return [];';
            $php .= "\n";
            $output->writeRaw($php);
            return 0;
        }

        $fileErrors = [];
        foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
            if (!$fileSpecificError->canBeIgnored()) {
                continue;
            }
            $fileErrors['/' . $this->relativePathHelper->getRelativePath($fileSpecificError->getFilePath())][] = $fileSpecificError;
        }
        ksort($fileErrors, SORT_STRING);

        $php = '<?php declare(strict_types = 1);';
        $php .= "\n\n";
        $php .= '$ignoreErrors = [];';
        $php .= "\n";
        foreach ($fileErrors as $file => $errors) {
            $fileErrorsByString = [];
            foreach ($errors as $error) {
                $string = $error->getIdentifier();
                $type = 'identifier';
                if ($string === NULL) {
                    $string = $error->getMessage();
                    $type = 'message';
                }

                if (!isset($fileErrorsByString[$string])) {
                    $fileErrorsByString[$string] = [1, $type];
                    continue;
                }

                $fileErrorsByString[$string][0]++;
              }
              ksort($fileErrorsByString, SORT_STRING);

              foreach ($fileErrorsByString as $string => [$count, $type]) {
                  if ($type === "identifier") {
                      $php .= sprintf(
                          "\$ignoreErrors[] = [\n\t'identifier' => %s,\n\t'count' => %d,\n\t'path' => __DIR__ . %s,\n];\n",
                          var_export($string, TRUE),
                          var_export($count, TRUE),
                          var_export($file, TRUE),
                      );
                  }
                  else {
                      $php .= sprintf(
                          "\$ignoreErrors[] = [\n\t'message' => %s,\n\t'count' => %d,\n\t'path' => __DIR__ . %s,\n];\n",
                          var_export('#^' . preg_quote($string, '#') . '$#', TRUE),
                          var_export($count, TRUE),
                          var_export($file, TRUE),
                      );
                  }
            }
        }

        $php .= "\n";
        $php .= 'return [\'parameters\' => [\'ignoreErrors\' => $ignoreErrors]];';
        $php .= "\n";

        $output->writeRaw($php);

        return 1;
    }

}
