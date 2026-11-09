<?php

declare(strict_types=1);

namespace IX\Tests\Unit\Providers\Theme;

use IX\Providers\Theme\Features\ContentPartial\ContentPartial;
use PHPUnit\Framework\TestCase;

/**
 * The "Header & Footer" box appears only on post types that render a page of their own.
 */
class ContentPartialOverridesTest extends TestCase
{
    protected function setUp(): void
    {
        register_post_type('ix_test_page_like', ['public' => true]);
        register_post_type('ix_test_private', ['public' => false, 'show_ui' => true]);
    }

    protected function tearDown(): void
    {
        unregister_post_type('ix_test_page_like');
        unregister_post_type('ix_test_private');
        remove_all_filters('ix/content_partial/supports_overrides');
    }

    public function testViewableTypesCanOverride(): void
    {
        $this->assertTrue(ContentPartial::supportsOverrides('ix_test_page_like'));
    }

    public function testPrivateTypesCannot(): void
    {
        $this->assertFalse(ContentPartial::supportsOverrides('ix_test_private'));
    }

    public function testAttachmentsUnknownAndEmptyTypesCannot(): void
    {
        $this->assertFalse(ContentPartial::supportsOverrides('attachment'));
        $this->assertFalse(ContentPartial::supportsOverrides('ix_test_missing'));
        $this->assertFalse(ContentPartial::supportsOverrides(''));
    }

    public function testASiteCanChangeTheAnswer(): void
    {
        add_filter('ix/content_partial/supports_overrides', static fn (bool $supports, string $type) => $type === 'ix_test_private' ? true : $supports, 10, 2);

        $this->assertTrue(ContentPartial::supportsOverrides('ix_test_private'));
    }

    public function testOnlyTheOverridesGroupIsNarrowed(): void
    {
        $feature = new ContentPartial();
        $screen  = ['post_type' => 'ix_test_private'];

        $this->assertFalse($feature->limitOverridesToViewable(true, [], $screen, ['key' => ContentPartial::OVERRIDES_GROUP]));
        $this->assertTrue($feature->limitOverridesToViewable(true, [], $screen, ['key' => 'group_something_else']));
        $this->assertFalse($feature->limitOverridesToViewable(false, [], ['post_type' => 'ix_test_page_like'], ['key' => ContentPartial::OVERRIDES_GROUP]));
    }
}
