<?php
/**
 * Server-side rendering for the Content Slider block.
 */

declare(strict_types=1);

use Timber\Timber;

// Assets load on demand: ThemeProvider registers them, this enqueues them
// wherever a slider actually renders.
wp_enqueue_style('ix-content-slider-block');
if (!is_admin()) {
    wp_enqueue_script('ix-content-slider-view');
}

$inner_blocks = $block?->inner_blocks ?? [];

// Render inner blocks to HTML strings.
$rendered_inner_blocks = [];
foreach ($inner_blocks as $inner_block) {
    $rendered_inner_blocks[] = $inner_block->render();
}

// Resolve attributes with block.json defaults so the data-* values are
// always present on the wrapper. view.js reads these to configure splide.
$showArrows       = !isset($attributes['showArrows']) || (bool) $attributes['showArrows'];
$autoplay         = !isset($attributes['autoplay']) || (bool) $attributes['autoplay'];
$autoplayInterval = isset($attributes['autoplayInterval']) ? (float) $attributes['autoplayInterval'] : 5.0;
if ($autoplayInterval <= 0) {
    $autoplayInterval = 5.0;
}
$centerContent    = !empty($attributes['centerContent']);
$transition       = (isset($attributes['transition']) && $attributes['transition'] === 'fade') ? 'fade' : 'slide';

$context = Timber::context();
$context['inner_blocks']     = $rendered_inner_blocks;
$context['show_arrows']      = $showArrows;
$context['autoplay']         = $autoplay;
$context['autoplay_interval'] = $autoplayInterval;
$context['transition']       = $transition;

/**
 * Extra Splide options for this slider, merged over the base config by view.js.
 *
 * The block's own attributes cover the options every site wants (arrows,
 * autoplay, fade). This seam covers the ones only one site wants — a three-up
 * press carousel, autoWidth, a custom gap — without IX having to grow an
 * attribute, a data-* key and an inspector control for each one, and without a
 * child theme having to replace the whole viewScript to get at Splide.
 *
 * Returning [] (the default) leaves behaviour exactly as it was.
 *
 * Runs per block instance and receives $attributes, so a filter can key off
 * anything the author set — most usefully `className`, which carries the
 * `is-style-*` of a registered block style. That keeps the author's control a
 * real one in the Styles panel while the mapping from style to config stays in
 * the child theme:
 *
 *     add_filter('ix/content_slider/splide_config', function (array $cfg, array $attributes): array {
 *         if (!str_contains($attributes['className'] ?? '', 'is-style-three-across')) {
 *             return $cfg;
 *         }
 *         return $cfg + ['perPage' => 3, 'pagination' => true];
 *     }, 10, 2);
 *
 * Values are passed to Splide verbatim, so this is a developer surface — it is
 * not reachable from the editor and nothing here is user input.
 *
 * @param array<string,mixed> $config     Splide options; empty by default.
 * @param array<string,mixed> $attributes This block's resolved attributes.
 * @param WP_Block|null       $block      The block instance.
 */
$splideConfig = apply_filters('ix/content_slider/splide_config', [], $attributes, $block);

$wrapperArgs = [
    'data-arrows'            => $showArrows ? 'true' : 'false',
    'data-autoplay'          => $autoplay ? 'true' : 'false',
    'data-autoplay-interval' => (string) $autoplayInterval,
    'data-center-content'    => $centerContent ? 'true' : 'false',
    'data-transition'        => $transition,
];

// Omit the attribute entirely when no filter ran, so unfiltered markup is
// byte-identical to what this block emitted before the seam existed.
if (is_array($splideConfig) && $splideConfig !== []) {
    $encoded = wp_json_encode($splideConfig);

    if ($encoded !== false) {
        $wrapperArgs['data-splide'] = $encoded;
    }
}

$wrapper_attributes = get_block_wrapper_attributes($wrapperArgs);

echo '<div ' . $wrapper_attributes . '>';
Timber::render(__DIR__ . '/content-slider.twig', $context);
echo '</div>';
