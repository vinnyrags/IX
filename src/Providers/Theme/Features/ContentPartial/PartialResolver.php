<?php

declare(strict_types=1);

namespace IX\Providers\Theme\Features\ContentPartial;

use WP_Post;
use WP_Query;

/**
 * Resolves which content partial should render for a given partial type
 * (header / footer / etc) on the current page.
 *
 * Cascade:
 *   1. Page-level mode = 'disabled'  → return null (render nothing)
 *   2. Page-level mode = 'custom' + partial set → return that partial
 *   3. Otherwise → return the partial flagged is_default for the type, or null
 */
class PartialResolver
{
    public static function resolveForType(string $type, ?int $pageId = null): ?WP_Post
    {
        if ($pageId !== null && $pageId > 0) {
            $mode = self::modeFor($type, $pageId);

            if ($mode === 'disabled') {
                return null;
            }

            if ($mode === 'custom') {
                $override = get_field("{$type}_partial", $pageId);
                if ($override instanceof WP_Post) {
                    return $override;
                }
                // Custom mode selected but no partial chosen — fall through
                // to the default rather than rendering nothing.
            }
        }

        return self::findDefaultForType($type);
    }

    /**
     * Whether the page has switched this partial type off (mode = 'disabled').
     *
     * Distinguishes "the editor turned the header off here" from "no partial
     * exists yet" — both resolve to null, but a template should render nothing
     * for the first and a fallback for the second.
     */
    public static function isDisabled(string $type, ?int $pageId = null): bool
    {
        return $pageId !== null && $pageId > 0 && self::modeFor($type, $pageId) === 'disabled';
    }

    private static function modeFor(string $type, int $pageId): string
    {
        return (string) (get_field("{$type}_mode", $pageId) ?: 'default');
    }

    private static function findDefaultForType(string $type): ?WP_Post
    {
        $query = new WP_Query([
            'post_type' => ContentPartialPost::POST_TYPE,
            'post_status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => ContentPartialPost::TAXONOMY,
                    'field' => 'slug',
                    'terms' => $type,
                ],
            ],
            'meta_query' => [
                [
                    'key' => 'is_default',
                    'value' => '1',
                ],
            ],
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'suppress_filters' => false,
        ]);

        return $query->posts[0] ?? null;
    }
}
