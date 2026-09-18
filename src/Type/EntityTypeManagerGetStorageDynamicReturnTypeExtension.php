<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use mglaman\PHPStanDrupal\Drupal\EntityDataRepository;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class EntityTypeManagerGetStorageDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

    public function __construct(
        private readonly EntityDataRepository $entityDataRepository
    ) {
    }

    public function getClass(): string
    {
        return EntityTypeManagerInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'getStorage';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        if ($methodCall->isFirstClassCallable()) {
            return null;
        }
        $args = $methodCall->getArgs();
        if (count($args) === 0) {
            return null;
        }

        $constantStrings = $scope->getType($args[0]->value)->getConstantStrings();
        if ($constantStrings === []) {
            // A dynamic entity type ID; fall back to the declared return type.
            return null;
        }

        $types = [];
        foreach ($constantStrings as $constantString) {
            $storageType = $this->entityDataRepository->get($constantString->getValue())->getStorageType();
            if ($storageType === null) {
                // An unknown entity type ID. Once one member is unknown the
                // whole call can only be trusted to the declared return type.
                return null;
            }
            $types[] = $storageType;
        }
        return TypeCombinator::union(...$types);
    }
}
