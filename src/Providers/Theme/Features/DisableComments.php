<?php

declare(strict_types=1);

namespace IX\Providers\Theme\Features;

use Mythus\Contracts\Feature;

/**
 * Disables all comment functionality across the site.
 *
 * The `comments_open` / `pings_open` filters are what actually stop comments —
 * they win regardless of what is stored per-post. The `default_*_status`
 * filters below do not change that behaviour at all; they exist so the stored
 * state agrees with it.
 *
 * Without them, WordPress keeps writing `comment_status = open` onto every new
 * post while the site refuses comments, which is misleading in three ways:
 * the Discussion settings screen contradicts the site, anything reading the
 * database (wp-cli, a migration, the next developer) concludes comments are
 * live, and opting out via `DisableComments::class => false` would open
 * comments on *all existing content at once* rather than just going forward.
 */
class DisableComments implements Feature
{
    public function register(): void
    {
        add_action('init', [$this, 'removePostTypeSupport'], 100);
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
        add_filter('comments_array', '__return_empty_array', 10, 2);

        // Keep the stored defaults in step with the enforced behaviour. Filters,
        // not writes — disabling this feature restores stock WordPress rather
        // than leaving the database permanently altered.
        add_filter('option_default_comment_status', [$this, 'forceClosed'], 20);
        add_filter('option_default_ping_status', [$this, 'forceClosed'], 20);

        add_action('admin_menu', [$this, 'removeAdminMenu']);
        add_action('admin_init', [$this, 'redirectAdminPage']);
        add_action('wp_before_admin_bar_render', [$this, 'removeFromAdminBar']);
    }

    /**
     * Force a comment/ping status option to 'closed'.
     *
     * WordPress expects the literal string 'closed' here, not a boolean — it is
     * written straight into the post's `comment_status` column on insert.
     */
    public function forceClosed(): string
    {
        return 'closed';
    }

    /**
     * Remove comment support from all post types.
     */
    public function removePostTypeSupport(): void
    {
        foreach (get_post_types() as $postType) {
            if (post_type_supports($postType, 'comments')) {
                remove_post_type_support($postType, 'comments');
                remove_post_type_support($postType, 'trackbacks');
            }
        }
    }

    /**
     * Remove comments from admin menu.
     */
    public function removeAdminMenu(): void
    {
        remove_menu_page('edit-comments.php');
    }

    /**
     * Redirect comments admin page to dashboard.
     */
    public function redirectAdminPage(): void
    {
        global $pagenow;

        if ($pagenow === 'edit-comments.php') {
            wp_safe_redirect(admin_url());
            $this->terminate();
        }
    }

    /**
     * Remove comments from admin bar.
     */
    public function removeFromAdminBar(): void
    {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('comments');
    }

    /**
     * Terminate script execution.
     *
     * Extracted to allow tests to override without killing the test runner.
     *
     * @codeCoverageIgnore
     */
    protected function terminate(): void
    {
        exit;
    }
}
