<?php

declare(strict_types=1);

namespace IX\Providers\Theme\Features\ContentPartial;

use Mythus\Contracts\Feature;
use WP_Post;

/**
 * Content-partial chrome: a reusable header/footer system driven by a
 * `content-partial` CPT.
 *
 * Editors author header/footer partials as CPT posts (tagged header/footer via the
 * `partial-type` taxonomy, one flagged `is_default` per type), and each page can
 * override which partial renders (default / custom / disabled) via the "Header &
 * Footer" field group. The resolved partials are injected into the Timber context
 * as `header_partial` / `footer_partial`, which the theme's `views/header.twig` and
 * `views/footer.twig` render — so **the theme still owns the header/footer markup**;
 * this Feature only supplies the data. A theme that builds its chrome directly in
 * those templates simply ignores the context vars.
 *
 * Registered by default in {@see \IX\Providers\Theme\ThemeProvider::$features}; a
 * consumer that builds its header/footer purely in-theme (no CPT) opts out with
 * `ContentPartial::class => false` in its ThemeProvider's `$features`.
 *
 * ACF field groups load from this Feature's acf-json/ (canonical keys, cross-site).
 */
class ContentPartial implements Feature
{
    private const DEFAULT_TERMS = ['header' => 'Header', 'footer' => 'Footer'];

    public function register(): void
    {
        add_action('init', [$this, 'registerPostType']);
        add_action('init', [$this, 'registerTaxonomy']);
        add_action('init', [$this, 'seedDefaultTerms'], 20);

        add_action(
            'save_post_' . ContentPartialPost::POST_TYPE,
            [$this, 'enforceDefaultUniqueness'],
            10,
            2
        );

        add_filter('acf/fields/post_object/query/key=field_partial_overrides_header_partial', [$this, 'filterHeaderPartialQuery']);
        add_filter('acf/fields/post_object/query/key=field_partial_overrides_footer_partial', [$this, 'filterFooterPartialQuery']);

        add_filter('acf/settings/load_json', [$this, 'registerAcfLoadPath']);
        add_filter('timber/context', [$this, 'addPartialsToContext']);
    }

    public function registerPostType(): void
    {
        $config = json_decode((string) file_get_contents(__DIR__ . '/config/post-type.json'), true);

        if (!is_array($config) || empty($config['post_type']) || empty($config['args'])) {
            return;
        }

        register_post_type($config['post_type'], $config['args']);
    }

    public function registerTaxonomy(): void
    {
        register_taxonomy(
            ContentPartialPost::TAXONOMY,
            ContentPartialPost::POST_TYPE,
            [
                'labels' => [
                    'name' => __('Partial Types', 'ix'),
                    'singular_name' => __('Partial Type', 'ix'),
                    'menu_name' => __('Types', 'ix'),
                ],
                'public' => false,
                'publicly_queryable' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => false,
                'show_in_rest' => true,
                'hierarchical' => true,
                'rewrite' => false,
            ]
        );
    }

    public function seedDefaultTerms(): void
    {
        if (!taxonomy_exists(ContentPartialPost::TAXONOMY)) {
            return;
        }

        foreach (self::DEFAULT_TERMS as $slug => $name) {
            if (!term_exists($slug, ContentPartialPost::TAXONOMY)) {
                wp_insert_term($name, ContentPartialPost::TAXONOMY, ['slug' => $slug]);
            }
        }
    }

    /**
     * Keep at most one is_default partial per type — checking a new default
     * unchecks the previous one.
     */
    public function enforceDefaultUniqueness(int $postId, WP_Post $post): void
    {
        if (wp_is_post_revision($postId)) {
            return;
        }

        if (!get_field('is_default', $postId)) {
            return;
        }

        $terms = wp_get_post_terms($postId, ContentPartialPost::TAXONOMY, ['fields' => 'slugs']);
        if (is_wp_error($terms) || empty($terms)) {
            return;
        }
        $type = $terms[0];

        $others = get_posts([
            'post_type' => ContentPartialPost::POST_TYPE,
            'post_status' => 'any',
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
            'post__not_in' => [$postId],
            'fields' => 'ids',
            'posts_per_page' => -1,
        ]);

        foreach ($others as $otherId) {
            update_field('is_default', false, $otherId);
        }
    }

    /**
     * Load this Feature's canonical field groups (Partial Settings + Header &
     * Footer) from its own acf-json/, cross-site.
     *
     * @param array<int, string> $paths
     * @return array<int, string>
     */
    public function registerAcfLoadPath(array $paths): array
    {
        $paths[] = __DIR__ . '/acf-json';

        return $paths;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function filterHeaderPartialQuery(array $args): array
    {
        return $this->scopeQueryToTerm($args, 'header');
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function filterFooterPartialQuery(array $args): array
    {
        return $this->scopeQueryToTerm($args, 'footer');
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private function scopeQueryToTerm(array $args, string $termSlug): array
    {
        $args['tax_query'] = [
            [
                'taxonomy' => ContentPartialPost::TAXONOMY,
                'field' => 'slug',
                'terms' => $termSlug,
            ],
        ];

        return $args;
    }

    /**
     * Inject the resolved header/footer partials into the Timber context so the
     * theme's header.twig / footer.twig can render them.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function addPartialsToContext(array $context): array
    {
        $pageId = is_singular() ? get_queried_object_id() : null;

        foreach (['header', 'footer'] as $type) {
            $partial = PartialResolver::resolveForType($type, $pageId);
            $context["{$type}_partial"] = $partial ? [
                'id' => $partial->ID,
                'slug' => $partial->post_name,
                'title' => $partial->post_title,
                'content' => apply_filters('the_content', $partial->post_content),
            ] : null;
        }

        return $context;
    }
}
