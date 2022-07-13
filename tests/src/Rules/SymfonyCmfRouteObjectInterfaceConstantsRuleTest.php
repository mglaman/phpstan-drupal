<?php
declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Deprecations\SymfonyCmfRouteObjectInterfaceConstantsRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class SymfonyCmfRouteObjectInterfaceConstantsRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new SymfonyCmfRouteObjectInterfaceConstantsRule();
    }

    public function testRule(): void
    {
        $this->analyse(
            [__DIR__.'/data/symfony-cmf-routing.php'],
            [
                [
                    'Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_NAME is deprecated and removed in Drupal 10. Use \Drupal\Core\Routing\RouteObjectInterface::ROUTE_NAME instead.',
                    6,
                    'Change record: https://www.drupal.org/node/3151009'
                ],
                [
                    'Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT is deprecated and removed in Drupal 10. Use \Drupal\Core\Routing\RouteObjectInterface::ROUTE_OBJECT instead.',
                    7,
                    'Change record: https://www.drupal.org/node/3151009'
                ],
                [
                    'Symfony\Cmf\Component\Routing\RouteObjectInterface::CONTROLLER_NAME is deprecated and removed in Drupal 10. Use \Drupal\Core\Routing\RouteObjectInterface::CONTROLLER_NAME instead.',
                    8,
                    'Change record: https://www.drupal.org/node/3151009'
                ],
                [
                    'The core dependency symfony-cmf/routing is deprecated and Symfony\Cmf\Component\Routing\RouteObjectInterface::TEMPLATE_NAME is not supported.',
                    9,
                    'Change record: https://www.drupal.org/node/3151009'
                ],
            ]
        );
    }

}
