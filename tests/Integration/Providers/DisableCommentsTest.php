<?php

namespace IX\Tests\Integration\Providers;

use IX\Providers\Theme\Features\DisableComments;
use Mythus\Contracts\Feature;
use Mythus\Contracts\Registrable;
use WorDBless\BaseTestCase;

/**
 * Integration tests for the DisableComments feature.
 */
class DisableCommentsTest extends BaseTestCase
{
    private DisableComments $feature;

    public function set_up(): void
    {
        parent::set_up();
        $this->feature = new DisableComments();
    }

    /**
     * Test that DisableComments implements Registrable.
     */
    public function testImplementsRegistrable(): void
    {
        $this->assertInstanceOf(Registrable::class, $this->feature);
    }

    /**
     * Test that DisableComments implements Feature (toggleable).
     */
    public function testImplementsFeature(): void
    {
        $this->assertInstanceOf(Feature::class, $this->feature);
    }

    /**
     * Test that register method adds WordPress hooks.
     */
    public function testRegisterAddsHooks(): void
    {
        $this->feature->register();

        // Check that actions were added
        $this->assertGreaterThan(
            0,
            has_action('init', [$this->feature, 'removePostTypeSupport'])
        );

        $this->assertGreaterThan(
            0,
            has_action('admin_menu', [$this->feature, 'removeAdminMenu'])
        );

        $this->assertGreaterThan(
            0,
            has_action('admin_init', [$this->feature, 'redirectAdminPage'])
        );

        $this->assertGreaterThan(
            0,
            has_action('wp_before_admin_bar_render', [$this->feature, 'removeFromAdminBar'])
        );
    }

    /**
     * Test that register adds comments_open filter.
     */
    public function testRegisterAddsCommentsOpenFilter(): void
    {
        $this->feature->register();

        $this->assertGreaterThan(
            0,
            has_filter('comments_open', '__return_false')
        );
    }

    /**
     * Test that register adds pings_open filter.
     */
    public function testRegisterAddsPingsOpenFilter(): void
    {
        $this->feature->register();

        $this->assertGreaterThan(
            0,
            has_filter('pings_open', '__return_false')
        );
    }

    /**
     * Test that register adds comments_array filter.
     */
    public function testRegisterAddsCommentsArrayFilter(): void
    {
        $this->feature->register();

        $this->assertGreaterThan(
            0,
            has_filter('comments_array', '__return_empty_array')
        );
    }

    /**
     * Test that comments_open filter returns false after registration.
     */
    public function testCommentsOpenFilterReturnsFalse(): void
    {
        $this->feature->register();

        // Filter should return false regardless of the input
        $result = apply_filters('comments_open', true, 123);
        $this->assertFalse($result);

        $result = apply_filters('comments_open', true, 456);
        $this->assertFalse($result);
    }

    /**
     * Test that pings_open filter returns false after registration.
     */
    public function testPingsOpenFilterReturnsFalse(): void
    {
        $this->feature->register();

        $result = apply_filters('pings_open', true, 123);
        $this->assertFalse($result);
    }

    /**
     * Test that comments_array filter returns empty array after registration.
     */
    public function testCommentsArrayFilterReturnsEmptyArray(): void
    {
        $this->feature->register();

        $comments = [
            ['comment_ID' => 1, 'comment_content' => 'Test comment'],
            ['comment_ID' => 2, 'comment_content' => 'Another comment'],
        ];

        $result = apply_filters('comments_array', $comments, 123);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that register adds the default status filters.
     */
    public function testRegisterAddsDefaultStatusFilters(): void
    {
        $this->feature->register();

        $this->assertGreaterThan(
            0,
            has_filter('option_default_comment_status', [$this->feature, 'forceClosed'])
        );

        $this->assertGreaterThan(
            0,
            has_filter('option_default_ping_status', [$this->feature, 'forceClosed'])
        );
    }

    /**
     * Test that the stored default comment status reads as closed.
     */
    public function testDefaultCommentStatusFilterReturnsClosed(): void
    {
        $this->feature->register();

        $this->assertSame('closed', apply_filters('option_default_comment_status', 'open'));
    }

    /**
     * Test that the stored default ping status reads as closed.
     */
    public function testDefaultPingStatusFilterReturnsClosed(): void
    {
        $this->feature->register();

        $this->assertSame('closed', apply_filters('option_default_ping_status', 'open'));
    }

    /**
     * The value must be the string 'closed', not a boolean.
     *
     * WordPress writes this straight into the post's `comment_status` column on
     * insert. Returning false would store an empty string, which is not a valid
     * status and would not read back as closed.
     */
    public function testForceClosedReturnsStringNotBoolean(): void
    {
        $this->assertSame('closed', $this->feature->forceClosed());
        $this->assertIsString($this->feature->forceClosed());
    }

    /**
     * Without the feature registered, the defaults must be untouched.
     *
     * This is what keeps the opt-out (`DisableComments::class => false`) honest:
     * disabling the feature has to restore stock WordPress behaviour, not leave
     * a filter behind.
     */
    public function testDefaultsAreUntouchedWhenNotRegistered(): void
    {
        $this->assertSame('open', apply_filters('option_default_comment_status', 'open'));
        $this->assertSame('open', apply_filters('option_default_ping_status', 'open'));
    }
}
