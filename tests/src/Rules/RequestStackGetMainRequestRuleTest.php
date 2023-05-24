<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\DrupalRequestStackShimInstantiationRule;
use mglaman\PHPStanDrupal\Rules\Drupal\RequestStackGetMainRequestRule;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;

final class RequestStackGetMainRequestRuleTest extends DrupalRuleTestCase
{

    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new RequestStackGetMainRequestRule();
    }

    public function testRule(): void
    {
        [$version] = explode('.', \Drupal::VERSION, 2);
        if ($version === '8') {
            $this->analyse([__DIR__.'/data/request-stack.php'], []);
        } elseif ($version >= '10') {
            self::markTestSkipped('Not tested on 10.x.x or higher');
        } else {
            $this->analyse(
                [__DIR__.'/data/request-stack.php'],
                [
                    [
                        'Symfony\Component\HttpFoundation\RequestStack::getMasterRequest() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0 for Symfony 6 compatibility. Use the forward compatibility shim class Drupal\Core\Http\RequestStack and its getMainRequest() method instead.',
                        17,
                        'Change record: https://www.drupal.org/node/3253744',
                    ],
                    [
                        'Symfony\Component\HttpFoundation\RequestStack::getMasterRequest() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0 for Symfony 6 compatibility. Use the forward compatibility shim class Drupal\Core\Http\RequestStack and its getMainRequest() method instead.',
                        29,
                        'Change record: https://www.drupal.org/node/3253744',
                    ],
                ]
            );
        }
    }

}
