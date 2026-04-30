<?php
/**
 * Server-side rendering for the Content Slider block.
 */

use Timber\Timber;

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

$context = Timber::context();
$context['inner_blocks']     = $rendered_inner_blocks;
$context['show_arrows']      = $showArrows;
$context['autoplay']         = $autoplay;
$context['autoplay_interval'] = $autoplayInterval;

$wrapper_attributes = get_block_wrapper_attributes([
    'data-arrows'            => $showArrows ? 'true' : 'false',
    'data-autoplay'          => $autoplay ? 'true' : 'false',
    'data-autoplay-interval' => (string) $autoplayInterval,
]);

echo '<div ' . $wrapper_attributes . '>';
Timber::render(__DIR__ . '/content-slider.twig', $context);
echo '</div>';
