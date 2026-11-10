<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback;

use Drupal\Core\Render\Element\RenderCallbackInterface;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\Security\TrustedCallbackInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassNode;
use function array_keys;
use function count;
use function strtolower;

/**
 * Collects the classes whose callbacks Drupal checks with trustedCallbacks().
 *
 * Classes implementing RenderCallbackInterface are left out: Drupal trusts
 * all their public methods.
 *
 * The rule joining the collected data cannot use reflection, because PHPStan
 * reuses its errors until the collected data changes. So everything the rule
 * needs about a class is collected here.
 *
 * @phpstan-type TrustedCallbackClass array{
 *     name: string,
 *     concrete: bool,
 *     parents: list<string>,
 *     interfaces: list<string>,
 *     trustedCallbacksClass: string,
 *     attributeMethods: list<string>,
 *     declaredMethods: list<string>,
 * }
 * @implements Collector<InClassNode, TrustedCallbackClass>
 */
final class TrustedCallbackClassCollector implements Collector
{

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $node->getClassReflection();
        if ($classReflection->isInterface()
            || $classReflection->isTrait()
            || !$classReflection->implementsInterface(TrustedCallbackInterface::class)
            || $classReflection->implementsInterface(RenderCallbackInterface::class)
        ) {
            return null;
        }

        $attributeMethods = [];
        $declaredMethods = [];
        foreach ($classReflection->getNativeReflection()->getMethods() as $method) {
            if (count($method->getAttributes(TrustedCallback::class)) > 0) {
                $attributeMethods[] = $method->getName();
            }
            if ($method->getDeclaringClass()->getName() === $classReflection->getName()) {
                $declaredMethods[] = strtolower($method->getName());
            }
        }

        return [
            'name' => $classReflection->getName(),
            'concrete' => !$classReflection->isAbstract(),
            'parents' => $classReflection->getParentClassesNames(),
            'interfaces' => array_keys($classReflection->getInterfaces()),
            'trustedCallbacksClass' => $classReflection->getNativeMethod('trustedCallbacks')->getDeclaringClass()->getName(),
            'attributeMethods' => $attributeMethods,
            'declaredMethods' => $declaredMethods,
        ];
    }
}
