<?php

declare(strict_types=1);

namespace IX\Providers\Theme\Features;

use Mythus\Contracts\Feature;

/**
 * Opts in to core's bundled block theme styles (`wp-block-styles`).
 *
 * That support loads `wp-includes/css/dist/block-library/theme.min.css` —
 * core's own visual defaults for quotes, pullquotes, separators, tables,
 * captions and code. Those are design choices, so they belong to a toggle a
 * child theme can turn off rather than a hardcoded theme support it has to
 * claw back with `remove_theme_support()` at a later priority.
 *
 * Registered by default in {@see \IX\Providers\Theme\ThemeProvider::$features}
 * so existing sites render unchanged; a child theme opts out with
 * `CoreBlockStyles::class => false`.
 */
class CoreBlockStyles implements Feature
{
    public function register(): void
    {
        add_action('after_setup_theme', [$this, 'addSupport']);
    }

    public function addSupport(): void
    {
        add_theme_support('wp-block-styles');
    }
}
