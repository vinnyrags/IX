<?php

namespace IX\Tests\Integration\Providers;

use IX\Providers\Theme\ThemeProvider;
use IX\Services\IconServiceFactory;
use IX\Tests\Support\HasContainer;
use WorDBless\BaseTestCase;

/**
 * Content Slider assets are registered on every frontend request but only
 * enqueued where the block is used.
 */
class ContentSliderAssetsTest extends BaseTestCase
{
    use HasContainer;

    public function set_up(): void
    {
        parent::set_up();

        // Resolve the parent theme to this checkout so its dist/ is found.
        add_filter('template_directory', [$this, 'ixRoot']);

        if (!file_exists(get_template_directory() . '/dist/css/content-slider.css')) {
            $this->markTestSkipped('Run `npm run build` first: the assets are only registered when dist/ exists.');
        }
    }

    public function tear_down(): void
    {
        wp_deregister_style('ix-content-slider-block');
        wp_deregister_script('ix-content-slider-view');
        remove_filter('template_directory', [$this, 'ixRoot']);
        parent::tear_down();
    }

    public function ixRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    public function testRegisteredButNotEnqueuedWithoutTheBlock(): void
    {
        $this->provider(false)->enqueueBlockAssetsForTest();

        $this->assertTrue(wp_style_is('ix-content-slider-block', 'registered'));
        $this->assertTrue(wp_script_is('ix-content-slider-view', 'registered'));
        $this->assertFalse(wp_style_is('ix-content-slider-block', 'enqueued'));
        $this->assertFalse(wp_script_is('ix-content-slider-view', 'enqueued'));
    }

    public function testEnqueuedWhenTheQueriedPostHasTheBlock(): void
    {
        $this->provider(true)->enqueueBlockAssetsForTest();

        $this->assertTrue(wp_style_is('ix-content-slider-block', 'enqueued'));
        $this->assertTrue(wp_script_is('ix-content-slider-view', 'enqueued'));
    }

    private function provider(bool $hasBlock): ThemeProvider
    {
        $container = $this->buildTestContainer();

        return new class ($container, $container->get(IconServiceFactory::class), $hasBlock) extends ThemeProvider {
            public function __construct($container, $iconFactory, private readonly bool $hasBlock)
            {
                parent::__construct($container, $iconFactory);
            }

            protected function queriedPostHasBlock(string $blockName): bool
            {
                return $this->hasBlock;
            }

            public function enqueueBlockAssetsForTest(): void
            {
                $this->setup();
                $this->enqueueBlockAssets();
            }
        };
    }
}
