<?php declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules;

use mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback\RenderCallbackCollector;
use mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback\RenderCallbackResolver;
use mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback\RenderCallbackTrustedCallbacksRule;
use mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback\TrustedCallbackClassCollector;
use mglaman\PHPStanDrupal\Rules\Drupal\RenderCallback\TrustedCallbacksCollector;
use mglaman\PHPStanDrupal\Tests\DrupalRuleTestCase;
use PHPStan\Rules\Rule;

/**
 * @extends DrupalRuleTestCase<RenderCallbackTrustedCallbacksRule>
 */
final class RenderCallbackTrustedCallbacksRuleTest extends DrupalRuleTestCase {

    private const TIP = "Add '%s' to the array trustedCallbacks() returns, or add the #[\\Drupal\\Core\\Security\\Attribute\\TrustedCallback] attribute to the method. Change record: https://www.drupal.org/node/2966725.";

    protected function getRule(): Rule
    {
        return new RenderCallbackTrustedCallbacksRule();
    }

    protected function getCollectors(): array
    {
        return [
            new RenderCallbackCollector(self::getContainer()->getByType(RenderCallbackResolver::class)),
            new TrustedCallbackClassCollector(),
            new TrustedCallbacksCollector(),
        ];
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/bug-561.php'], [
            [
                "#pre_render callback method 'unlisted' at key '0' is not returned by Bug561\\NonFinalCallbacks::trustedCallbacks().",
                82,
                sprintf(self::TIP, 'unlisted'),
            ],
            [
                "#pre_render callback method 'unlisted' at key '0' is not returned by Bug561\\FinalCallbacks::trustedCallbacks().",
                100,
                sprintf(self::TIP, 'unlisted'),
            ],
            [
                "#pre_render callback method 'unlisted' at key '0' is not returned by Bug561\\ListedCallbacks::trustedCallbacks().",
                123,
                sprintf(self::TIP, 'unlisted'),
            ],
            [
                "#pre_render callback method 'unlisted' at key '0' is not returned by Bug561\\ListedCallbacks::trustedCallbacks().",
                124,
                sprintf(self::TIP, 'unlisted'),
            ],
            [
                "#pre_render callback method 'unlisted' at key '0' is not returned by Bug561\\InheritedCallbacks::trustedCallbacks().",
                126,
                sprintf(self::TIP, 'unlisted'),
            ],
            [
                "#pre_render callback method 'unlisted' at key '0' is not returned by Bug561\\TraitCallbacks::trustedCallbacks().",
                129,
                sprintf(self::TIP, 'unlisted'),
            ],
            [
                "#lazy_builder callback method 'unlisted' at key '0' is not returned by Bug561\\ListedCallbacks::trustedCallbacks().",
                130,
                sprintf(self::TIP, 'unlisted'),
            ],
            [
                "#access_callback callback method 'unlisted' at key '0' is not returned by Bug561\\ListedCallbacks::trustedCallbacks().",
                131,
                sprintf(self::TIP, 'unlisted'),
            ],
            [
                "#pre_render callback method 'preRender' at key '0' is not returned by Bug561\\SubclassMissesMethod::trustedCallbacks().",
                141,
                sprintf(self::TIP, 'preRender'),
            ],
            [
                "#pre_render callback method 'unlisted' at key '1' is not returned by Bug561\\AppendedCallbacks::trustedCallbacks().",
                202,
                sprintf(self::TIP, 'unlisted'),
            ],
            [
                "#pre_render callback method 'traitCallback' at key '0' is not returned by Bug561\\TraitSiteMissesMethod::trustedCallbacks().",
                257,
                sprintf(self::TIP, 'traitCallback'),
            ],
            [
                "#pre_render callback method 'unlisted' at key '0' is not returned by Bug561\\ConditionalCallbacks::trustedCallbacks().",
                294,
                sprintf(self::TIP, 'unlisted'),
            ],
            [
                "#pre_render callback method 'preRender' at key '0' is not returned by Bug561\\AbstractBaseWithoutSubclasses::trustedCallbacks().",
                295,
                sprintf(self::TIP, 'preRender'),
            ],
            [
                "#pre_render callback method 'buildMultiple' at key '0' is not returned by Bug561\\ViewBuilderInheritsSite::trustedCallbacks().",
                305,
                sprintf(self::TIP, 'buildMultiple'),
            ],
            [
                "#pre_render callback method 'preRender' at key '1' is not returned by Bug561\\CallbackServiceMissesMethod::trustedCallbacks().",
                375,
                sprintf(self::TIP, 'preRender'),
            ],
        ]);
    }

    public function testCallbackClassDeclaredInAnotherClassFile(): void
    {
        $this->analyse([
            __DIR__ . '/../../fixtures/drupal/modules/pre_render_callback_rule/src/PlaceholderTestBase.php',
            __DIR__ . '/../../fixtures/drupal/modules/pre_render_callback_rule/src/PlaceholderGeneratorTest.php',
        ], [
            [
                "#lazy_builder callback method 'unlistedCallback' at key '0' is not returned by Drupal\\pre_render_callback_rule\\PlaceholderCallbacks::trustedCallbacks().",
                16,
                sprintf(self::TIP, 'unlistedCallback'),
            ],
        ]);
    }

    public function testCallbackClassNotAnalyzed(): void
    {
        $this->analyse([
            __DIR__ . '/../../fixtures/drupal/modules/pre_render_callback_rule/src/PlaceholderGeneratorTest.php',
        ], []);
    }

}
