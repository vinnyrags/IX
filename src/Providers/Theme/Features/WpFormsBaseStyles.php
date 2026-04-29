<?php

declare(strict_types=1);

namespace IX\Providers\Theme\Features;

use Mythus\Contracts\Feature;

/**
 * Opt-in WPForms base field styles.
 *
 * Enqueues generic structural CSS for WPForms — field margins, full-width
 * inputs, textarea height/resize, submit container spacing, honeypot
 * exclusion. Brand-specific opinion (error colors, accent labels) lives
 * in the consumer theme.
 *
 * Not included in parent's $features by default — child themes opt in
 * by adding WpFormsBaseStyles::class to their $features array. Pair with
 * WpFormsBlockDetection to load WPForms global assets only on pages that
 * have form blocks, and with WpFormsFloatingLabels to layer the floating-
 * label visual treatment on top of these base styles.
 */
class WpFormsBaseStyles implements Feature
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('enqueue_block_assets', [$this, 'enqueueEditorAssets']);
    }

    public function enqueueAssets(): void
    {
        $cssPath = get_template_directory() . '/dist/css/features/wpforms-base.css';

        if (file_exists($cssPath)) {
            wp_enqueue_style(
                'ix-wpforms-base',
                get_template_directory_uri() . '/dist/css/features/wpforms-base.css',
                [],
                filemtime($cssPath)
            );
        }
    }

    public function enqueueEditorAssets(): void
    {
        if (! is_admin()) {
            return;
        }

        $cssPath = get_template_directory() . '/dist/css/features/wpforms-base.css';

        if (file_exists($cssPath)) {
            wp_enqueue_style(
                'ix-wpforms-base-editor',
                get_template_directory_uri() . '/dist/css/features/wpforms-base.css',
                [],
                filemtime($cssPath)
            );
        }
    }
}
