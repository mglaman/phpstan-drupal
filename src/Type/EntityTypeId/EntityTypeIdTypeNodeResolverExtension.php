<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type\EntityTypeId;

use mglaman\PHPStanDrupal\Drupal\EntityDataRepository;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Type;

class EntityTypeIdTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    private EntityDataRepository $entityDataRepository;

    public function __construct(EntityDataRepository $entityDataRepository)
    {
        $this->entityDataRepository = $entityDataRepository;
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if (!$typeNode instanceof IdentifierTypeNode) {
            return null;
        }
        if ($typeNode->name !== 'entity-type-id') {
            return null;
        }

        return new EntityTypeIdType($this->entityDataRepository->getAllEntityTypeIds());
    }
}
