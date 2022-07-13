<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\DrupalRequestStackShimInClassMethodRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class DrupalRequestStackShimInClassMethodRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new DrupalRequestStackShimInClassMethodRule();
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
                        'Parameter $stack of method __construct() uses \Symfony\Component\HttpFoundation\RequestStack. Use \Drupal\Core\Http\RequestStack instead for forward compatibility to Symfony 5.',
                        6,
                        'Change record: https://www.drupal.org/node/3253744',
                    ],
                    [
                        'Return type of method %s::%s() uses \Symfony\Component\HttpFoundation\RequestStack. Use \Drupal\Core\Http\RequestStack instead for forward compatibility to Symfony 5.',
                        9,
                        'Change record: https://www.drupal.org/node/3253744',
                    ],
                ]
            );
        }
    }

}
