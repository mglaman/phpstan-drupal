<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Type;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

final class ServiceIdTypeNodeResolverExtension implements TypeNodeResolverExtension
{

    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?\PHPStan\Type\Type
    {
        if ($typeNode instanceof IdentifierTypeNode && $typeNode->__toString() === 'service-id-string') {
            return new \mglaman\PHPStanDrupal\Type\ServiceIdType();
        }

        return null;
    }
}
