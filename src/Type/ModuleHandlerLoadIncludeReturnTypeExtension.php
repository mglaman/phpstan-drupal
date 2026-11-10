<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\Extension\ModuleHandlerInterface;
use mglaman\PHPStanDrupal\Drupal\IncludeLoader;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use function count;

/**
 * Loads the file a ModuleHandlerInterface::loadInclude() call includes.
 *
 * This keeps the functions in the file known when the LoadIncludes rule is
 * turned off, or when a project only includes extension.neon. The return type
 * itself doesn't change.
 */
final class ModuleHandlerLoadIncludeReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    public function __construct(
        private readonly IncludeLoader $includeLoader
    ) {
    }

    public function getClass(): string
    {
        return ModuleHandlerInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'loadInclude';
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $args = $methodCall->getArgs();
        if (count($args) >= 2) {
            $include = $this->includeLoader->resolve($args[0], $args[1], $args[2] ?? null, $scope);
            if ($include !== null) {
                $this->includeLoader->load($include[0], $include[1], $scope);
            }
        }
        return ParametersAcceptorSelector::selectFromArgs(
            $scope,
            $args,
            $methodReflection->getVariants()
        )->getReturnType();
    }
}
