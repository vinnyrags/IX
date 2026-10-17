<?php

namespace IX\Tests\Integration\Providers\Features;

use IX\Providers\Theme\Features\AccordionIconEnhancer;
use IX\Providers\Theme\Features\CoreBlockStyles;
use IX\Providers\Theme\ThemeProvider;
use Mythus\Contracts\Feature;
use ReflectionClass;
use WorDBless\BaseTestCase;

/**
 * Integration tests for CoreBlockStyles, and for which of IX's opinionated
 * behaviours ship on by default.
 */
class CoreBlockStylesTest extends BaseTestCase
{
    public function tear_down(): void
    {
        remove_theme_support('wp-block-styles');
        parent::tear_down();
    }

    public function testImplementsFeature(): void
    {
        $this->assertInstanceOf(Feature::class, new CoreBlockStyles());
    }

    public function testAddSupportEnablesBlockStyles(): void
    {
        (new CoreBlockStyles())->addSupport();

        $this->assertTrue(current_theme_supports('wp-block-styles'));
    }

    public function testRegisterDefersToAfterSetupTheme(): void
    {
        $feature = new CoreBlockStyles();
        $feature->register();

        $this->assertGreaterThan(0, has_action('after_setup_theme', [$feature, 'addSupport']));
    }

    public function testCoreBlockStylesIsOnByDefault(): void
    {
        $this->assertContains(CoreBlockStyles::class, $this->defaultFeatures());
    }

    public function testAccordionIconEnhancerIsOptIn(): void
    {
        $this->assertNotContains(AccordionIconEnhancer::class, $this->defaultFeatures());
    }

    /**
     * @return array<class-string>
     */
    private function defaultFeatures(): array
    {
        return (new ReflectionClass(ThemeProvider::class))
            ->getProperty('features')
            ->getDefaultValue();
    }
}
