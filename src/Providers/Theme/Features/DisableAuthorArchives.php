<?php

declare(strict_types=1);

namespace IX\Providers\Theme\Features;

use Mythus\Contracts\Feature;

/**
 * Hides author archives and the routes that expose WordPress login names.
 *
 * WordPress publishes valid usernames to anonymous visitors through four separate
 * routes. Closing only the obvious one leaves the account list readable:
 *
 *   1. /author/<login>/          the archive itself, which Google indexes
 *   2. /?author=<id>             301s to the archive, leaking the login in Location
 *   3. /wp-sitemap-users-1.xml   core feeds the archive URLs to search engines
 *   4. /wp-json/wp/v2/users      returns every user's slug as JSON
 *
 * This matters because knowing the username is most of a credential attack. On
 * 2026-08-11 an ARTHOUSE site was compromised by an attacker logging in as a known
 * administrator account — they did not guess the name, /author/<login>/ was indexed
 * and /?author=1 handed it over on request.
 *
 * Anyone who can already list users through the admin keeps normal behaviour, so the
 * block editor's author controls are unaffected. A site that genuinely wants public
 * author archives opts out in its child provider:
 *
 *   protected array $features = [
 *       DisableAuthorArchives::class => false,
 *   ];
 */
class DisableAuthorArchives implements Feature
{
    public function register(): void
    {
        add_action('template_redirect', [$this, 'blockAuthorRequests'], 0);
        add_filter('wp_sitemaps_add_provider', [$this, 'removeUsersSitemap'], 10, 2);
        add_filter('rest_endpoints', [$this, 'removeUsersEndpoints']);
    }

    /**
     * Return 404 for author archive requests, including the ?author=<id> form.
     *
     * Runs at priority 0 deliberately. Core registers redirect_canonical() on
     * template_redirect at priority 10, and that is what converts /?author=1 into a
     * 301 to /author/<login>/ — so setting a 404 alone is not enough, the canonical
     * redirect has to be unhooked before it fires or the login name still leaks in
     * the Location header.
     */
    public function blockAuthorRequests(): void
    {
        if (!is_author() || $this->viewerMayListUsers()) {
            return;
        }

        remove_action('template_redirect', 'redirect_canonical');

        global $wp_query;

        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }

    /**
     * Drop the users provider from the core sitemap.
     *
     * Core only generates this once an author has published a post, so its absence on
     * a given site is incidental rather than protective.
     *
     * @param mixed $provider The sitemap provider instance.
     * @param string $name Provider name.
     * @return mixed False to remove the provider, otherwise the provider unchanged.
     */
    public function removeUsersSitemap(mixed $provider, string $name): mixed
    {
        return $name === 'users' ? false : $provider;
    }

    /**
     * Hide the REST users routes from viewers who cannot already list users.
     *
     * Removing them outright would break the editor, which reads this endpoint to
     * populate author controls.
     *
     * @param array<string, mixed> $endpoints Registered REST endpoints.
     * @return array<string, mixed> Endpoints with the users routes removed where applicable.
     */
    public function removeUsersEndpoints(array $endpoints): array
    {
        if ($this->viewerMayListUsers()) {
            return $endpoints;
        }

        unset(
            $endpoints['/wp/v2/users'],
            $endpoints['/wp/v2/users/(?P<id>[\d]+)']
        );

        return $endpoints;
    }

    /**
     * Whether the current viewer can already enumerate users through the admin.
     */
    protected function viewerMayListUsers(): bool
    {
        return is_user_logged_in() && current_user_can('list_users');
    }
}
