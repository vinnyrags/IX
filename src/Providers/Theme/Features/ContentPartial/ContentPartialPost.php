<?php

declare(strict_types=1);

namespace IX\Providers\Theme\Features\ContentPartial;

use IX\Models\Post;

/**
 * Timber model for a content-partial post (a reusable header/footer chrome block).
 */
class ContentPartialPost extends Post
{
    public const POST_TYPE = 'content-partial';

    public const TAXONOMY = 'partial-type';

    /**
     * The taxonomy term slug assigned to this partial (e.g. 'header', 'footer').
     */
    public function partialType(): ?string
    {
        $terms = wp_get_post_terms($this->ID, self::TAXONOMY, ['fields' => 'slugs']);
        if (is_wp_error($terms) || empty($terms)) {
            return null;
        }
        return $terms[0];
    }

    public function isDefault(): bool
    {
        return (bool) get_field('is_default', $this->ID);
    }
}
