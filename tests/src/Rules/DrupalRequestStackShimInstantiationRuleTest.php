<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\DrupalRequestStackShimInstantiationRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class DrupalRequestStackShimInstantiationRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new DrupalRequestStackShimInstantiationRule();
    }

    public function testRule(): void
    {
        [$version] = explode('.', \Drupal::VERSION, 2);
        if ($version === '8') {
            $this->analyse([__DIR__.'/data/request-stack.php'], []);
        } elseif ($version === '10') {
            self::markTestSkipped('Not tested on 10.x.x');
        } else {
            $this->analyse(
                [__DIR__.'/data/request-stack.php'],
                [
                    [
                        'Do not instantiate Symfony\Component\HttpFoundation\RequestStack. Use \Drupal\Core\Http\RequestStack instead for forward compatibility to Symfony 5.',
                        11,
                        'Change record: https://www.drupal.org/node/3253744',
                    ],
                ]
            );
        }
    }

}
