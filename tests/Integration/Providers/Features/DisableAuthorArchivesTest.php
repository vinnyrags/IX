<?php

namespace IX\Tests\Integration\Providers\Features;

use IX\Providers\Theme\Features\DisableAuthorArchives;
use IX\Tests\Support\HasContainer;
use Mythus\Contracts\Feature;
use Mythus\Contracts\Registrable;
use WorDBless\BaseTestCase;

/**
 * Integration tests for the DisableAuthorArchives feature.
 */
class DisableAuthorArchivesTest extends BaseTestCase
{
    use HasContainer;

    private DisableAuthorArchives $feature;

    public function set_up(): void
    {
        parent::set_up();
        $container = $this->buildTestContainer();
        $this->feature = $container->get(DisableAuthorArchives::class);
    }

    /**
     * Test that DisableAuthorArchives implements Registrable.
     */
    public function testImplementsRegistrable(): void
    {
        $this->assertInstanceOf(Registrable::class, $this->feature);
    }

    /**
     * Test that DisableAuthorArchives implements Feature (opt-out per consumer).
     */
    public function testImplementsFeature(): void
    {
        $this->assertInstanceOf(Feature::class, $this->feature);
    }

    /**
     * Test that the author request guard runs at priority 0.
     *
     * This is the load-bearing detail of the whole feature. Core registers
     * redirect_canonical() on template_redirect at priority 10, and that is what
     * turns /?author=1 into a 301 whose Location header contains the login name.
     * Setting a 404 at any priority above 10 still leaks the username, because the
     * canonical redirect has already fired. If this assertion ever fails, the
     * feature looks like it works while the enumeration hole is wide open.
     */
    public function testAuthorGuardRunsBeforeCanonicalRedirect(): void
    {
        $this->feature->register();

        $this->assertSame(
            0,
            has_action('template_redirect', [$this->feature, 'blockAuthorRequests'])
        );
    }

    /**
     * Test that register hooks the sitemap and REST filters.
     */
    public function testRegisterAddsSitemapAndRestFilters(): void
    {
        $this->feature->register();

        $this->assertGreaterThan(
            0,
            has_filter('wp_sitemaps_add_provider', [$this->feature, 'removeUsersSitemap'])
        );
        $this->assertGreaterThan(
            0,
            has_filter('rest_endpoints', [$this->feature, 'removeUsersEndpoints'])
        );
    }

    /**
     * Test that the users sitemap provider is dropped.
     */
    public function testRemovesUsersSitemapProvider(): void
    {
        $this->assertFalse(
            $this->feature->removeUsersSitemap(new \stdClass(), 'users')
        );
    }

    /**
     * Test that other sitemap providers are left untouched.
     */
    public function testLeavesOtherSitemapProvidersAlone(): void
    {
        $provider = new \stdClass();

        $this->assertSame($provider, $this->feature->removeUsersSitemap($provider, 'posts'));
        $this->assertSame($provider, $this->feature->removeUsersSitemap($provider, 'taxonomies'));
    }

    /**
     * Test that anonymous callers lose the REST users routes.
     */
    public function testRemovesRestUsersRoutesForAnonymousViewer(): void
    {
        wp_set_current_user(0);

        $endpoints = $this->feature->removeUsersEndpoints([
            '/wp/v2/users' => 'collection',
            '/wp/v2/users/(?P<id>[\d]+)' => 'single',
            '/wp/v2/posts' => 'posts',
        ]);

        $this->assertArrayNotHasKey('/wp/v2/users', $endpoints);
        $this->assertArrayNotHasKey('/wp/v2/users/(?P<id>[\d]+)', $endpoints);
        $this->assertArrayHasKey('/wp/v2/posts', $endpoints);
    }

    /**
     * Test that users who can already list users keep the REST routes.
     *
     * The block editor reads this endpoint to populate author controls, so hiding it
     * from editors would break the admin rather than harden it.
     */
    public function testKeepsRestUsersRoutesForPrivilegedViewer(): void
    {
        $administrator = wp_insert_user([
            'user_login' => 'ix_test_admin',
            'user_pass' => wp_generate_password(),
            'role' => 'administrator',
        ]);

        wp_set_current_user($administrator);

        $endpoints = $this->feature->removeUsersEndpoints([
            '/wp/v2/users' => 'collection',
            '/wp/v2/posts' => 'posts',
        ]);

        $this->assertArrayHasKey('/wp/v2/users', $endpoints);
        $this->assertArrayHasKey('/wp/v2/posts', $endpoints);
    }

    /**
     * Test that non-author requests are left alone entirely.
     */
    public function testIgnoresNonAuthorRequests(): void
    {
        global $wp_query;

        $this->feature->register();
        $this->feature->blockAuthorRequests();

        $this->assertFalse($wp_query->is_404());
        $this->assertNotFalse(has_action('template_redirect', 'redirect_canonical'));
    }
}
