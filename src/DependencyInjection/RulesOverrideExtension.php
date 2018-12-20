<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Rules\Classes\RequireParentConstructCallRule;
use PHPStan\Rules\Classes\EnhancedRequireParentConstructCallRule;

class RulesOverrideExtension extends \Nette\DI\CompilerExtension
{
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
        foreach ($builder->getDefinitions() as $definition) {
            $factory = $definition->getFactory();
            if ($factory === null) {
                continue;
            }
            if ($factory->entity === RequireParentConstructCallRule::class) {
                $definition->setFactory(EnhancedRequireParentConstructCallRule::class);
            }
        }
    }
}
