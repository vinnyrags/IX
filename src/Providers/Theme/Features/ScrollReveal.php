<?php

declare(strict_types=1);

namespace IX\Providers\Theme\Features;

use Mythus\Contracts\Feature;

/**
 * Opt-in scroll reveal animations.
 *
 * Enqueues both halves of the system:
 *   - features/scroll-reveal.css — the .fade-up → .is-visible transitions
 *   - theme/scroll-reveal.js     — IntersectionObserver wiring + stagger
 *
 * Not included in the parent's $features by default — child themes opt in
 * by adding ScrollReveal::class to their own $features array.
 *
 * Three filters expose the runtime config so child themes can extend the
 * defaults without forking:
 *
 *   - 'ix/scroll_reveal_selectors'         (string[]) — CSS selectors to animate.
 *   - 'ix/scroll_reveal_exclude_ancestors' (string[]) — ancestor selectors that disqualify a match.
 *   - 'ix/scroll_reveal_stagger_delay'     (float)    — seconds between sibling reveals.
 */
class ScrollReveal implements Feature
{
    public const FILTER_SELECTORS         = 'ix/scroll_reveal_selectors';
    public const FILTER_EXCLUDE_ANCESTORS = 'ix/scroll_reveal_exclude_ancestors';
    public const FILTER_STAGGER_DELAY     = 'ix/scroll_reveal_stagger_delay';

    /**
     * Generic, theme-agnostic selectors that should fade up by default
     * when the feature is enabled. Child themes can extend this list via
     * the FILTER_SELECTORS filter.
     *
     * @var string[]
     */
    private const DEFAULT_SELECTORS = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        '.site-main p',
        '.site-main ul',
        '.site-main ol',
        '.site-main dl',
        '.site-main .wp-block-image',
        '.site-main .wp-block-button',
        '.site-main .wp-block-buttons',
        '.site-main .wp-block-table',
        '.site-main .wp-block-code',
        '.site-main cite',
    ];

    /**
     * Splide-driven content (e.g. ix/content-slider) handles its own
     * slide-in animation, so default-exclude any descendants of a
     * splide root.
     *
     * @var string[]
     */
    private const DEFAULT_EXCLUDE_ANCESTORS = ['.splide'];

    private const DEFAULT_STAGGER_DELAY = 0.1;

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        $this->enqueueStyle();
        $this->enqueueScript();
    }

    private function enqueueStyle(): void
    {
        $relPath = '/dist/css/features/scroll-reveal.css';
        $fullPath = get_template_directory() . $relPath;

        if (!file_exists($fullPath)) {
            return;
        }

        wp_enqueue_style(
            'ix-scroll-reveal',
            get_template_directory_uri() . $relPath,
            [],
            (string) filemtime($fullPath)
        );
    }

    private function enqueueScript(): void
    {
        $relPath = '/dist/js/theme/scroll-reveal.js';
        $fullPath = get_template_directory() . $relPath;

        if (!file_exists($fullPath)) {
            return;
        }

        wp_enqueue_script(
            'ix-scroll-reveal',
            get_template_directory_uri() . $relPath,
            [],
            (string) filemtime($fullPath),
            true
        );

        wp_localize_script('ix-scroll-reveal', 'ixScrollReveal', [
            'selectors'        => array_values(apply_filters(self::FILTER_SELECTORS, self::DEFAULT_SELECTORS)),
            'excludeAncestors' => array_values(apply_filters(self::FILTER_EXCLUDE_ANCESTORS, self::DEFAULT_EXCLUDE_ANCESTORS)),
            'staggerDelay'     => (float) apply_filters(self::FILTER_STAGGER_DELAY, self::DEFAULT_STAGGER_DELAY),
        ]);
    }
}
