<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Drupal;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

/**
 * Populates the Drupal service map when the bootstrap file is not executed.
 *
 * The service map is built by {@see DrupalAutoloader::register()}, which runs as
 * a `bootstrapFiles` entry (drupal-autoloader.php). PHPStan executes those through
 * CommandHelper::executeBootstrapFile(). Tools that build the PHPStan container
 * directly never call CommandHelper, so the bootstrap is skipped and the map stays
 * empty — most notably Rector, whose PHPStanServicesFactory calls
 * ContainerFactory::create() and stops there. The result is that \Drupal::service()
 * resolves to `object` under Rector instead of the concrete service class.
 *
 * This service is registered as a no-op dynamic return type extension purely so
 * PHPStan instantiates it eagerly when the broker is built. If the map is still
 * empty at that point, it runs the autoloader against the live container. The
 * empty-map guard keeps a normal PHPStan run a no-op, since the bootstrap already
 * populated the map there.
 *
 * @see https://github.com/mglaman/phpstan-drupal/issues/995
 */
final class RectorServiceMapInitializer implements DynamicStaticMethodReturnTypeExtension
{
    public function __construct(Container $container, ServiceMap $serviceMap)
    {
        if ($serviceMap->getServices() === []) {
            (new DrupalAutoloader())->register($container);
        }
    }

    public function getClass(): string
    {
        return 'Drupal';
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return false;
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
    {
        return null;
    }
}
