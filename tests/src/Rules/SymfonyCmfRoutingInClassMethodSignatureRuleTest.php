<?php
declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Deprecations\SymfonyCmfRoutingInClassMethodSignatureRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class SymfonyCmfRoutingInClassMethodSignatureRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new SymfonyCmfRoutingInClassMethodSignatureRule();
    }

    public function testRule(): void
    {
        [$version] = explode('.', \Drupal::VERSION, 2);
        if ($version === '8') {
            $this->analyse([__DIR__.'/data/symfony-cmf-routing.php'], []);
        } elseif ($version >= '10') {
            self::markTestSkipped('Not tested on 10.x.x or higher');
        } else {
            $this->analyse(
                [__DIR__.'/data/symfony-cmf-routing.php'],
                [
                    [
                        'Parameter $object of method a() uses deprecated Symfony\Cmf\Component\Routing\RouteObjectInterface and removed in Drupal 10. Use \Drupal\Core\Routing\RouteObjectInterface instead.',
                        10,
                        'Change record: https://www.drupal.org/node/3151009'
                    ],
                    [
                        'Parameter $provider of method b() uses deprecated Symfony\Cmf\Component\Routing\RouteProviderInterface and removed in Drupal 10. Use \Drupal\Core\Routing\RouteProviderInterface instead.',
                        13,
                        'Change record: https://www.drupal.org/node/3151009'
                    ],
                    [
                        'Return type of method SymfonyCmfRoutingUsage\Foo::b() has typehint with deprecated Symfony\Cmf\Component\Routing\RouteObjectInterface and is removed in Drupal 10. Use \Drupal\Core\Routing\RouteObjectInterface instead.',
                        13,
                        'Change record: https://www.drupal.org/node/3151009'
                    ],
                    [
                        'Parameter $collection of method c() uses deprecated Symfony\Cmf\Component\Routing\LazyRouteCollection and removed in Drupal 10. Use \Drupal\Core\Routing\LazyRouteCollection instead.',
                        16,
                        'Change record: https://www.drupal.org/node/3151009'
                    ],
                ]
            );
        }
    }

}
