/**
 * Content Slider carousel
 * Initializes Splide on .content-slider.splide containers for an
 * accessible carousel experience over arbitrary inner blocks.
 *
 * Per-block toggles (arrows, autoplay) come from data-arrows /
 * data-autoplay on the block wrapper (set by render.php from the
 * showArrows / autoplay attributes). Defaults match block.json
 * (both true).
 */

import Splide from '@splidejs/splide';

export const SPLIDE_BASE_CONFIG = {
    type: 'loop',
    perPage: 1,
    pagination: false,
    speed: 400,
    gap: '2rem',
    pauseOnHover: true,
    i18n: {
        prev: 'Previous slide',
        next: 'Next slide',
        slideX: 'Go to slide %s',
        pageX: 'Go to page %s',
    },
};

/**
 * Resolve the wrapper element that carries the per-block data attributes.
 */
function getWrapper(carousel) {
    return carousel.closest('.wp-block-ix-content-slider') || carousel.parentElement;
}

/**
 * Read a boolean data attribute. Falls back to `fallback` if missing.
 */
function readBoolAttr(carousel, attrName, fallback) {
    const wrapper = getWrapper(carousel);
    if (!wrapper) return fallback;
    const value = wrapper.getAttribute(attrName);
    if (value === null) return fallback;
    return value === 'true';
}

/**
 * Read a numeric data attribute. Falls back to `fallback` if missing or
 * not a positive finite number.
 */
function readNumberAttr(carousel, attrName, fallback) {
    const wrapper = getWrapper(carousel);
    if (!wrapper) return fallback;
    const raw = wrapper.getAttribute(attrName);
    const num = parseFloat(raw);
    return Number.isFinite(num) && num > 0 ? num : fallback;
}

/**
 * Initialize content slider carousels on the page.
 * Skips initialization if fewer than 2 slides.
 */
export function initContentSlider() {
    const carousels = document.querySelectorAll('.content-slider.splide:not(.is-initialized)');

    carousels.forEach((carousel) => {
        const slides = carousel.querySelectorAll('.splide__slide');

        if (slides.length < 2) {
            // Splide's stylesheet sets visibility:hidden on .splide and only
            // lifts it when .is-initialized is present. We skip Splide for
            // single-slide carousels (no carousel chrome makes sense for 1
            // slide), so add the class manually — the slide renders as static
            // content but stays visible.
            carousel.classList.add('is-initialized');
            return;
        }

        const showArrows       = readBoolAttr(carousel, 'data-arrows', true);
        const autoplay         = readBoolAttr(carousel, 'data-autoplay', true);
        const autoplayIntervalSec = readNumberAttr(carousel, 'data-autoplay-interval', 5);

        new Splide(carousel, {
            ...SPLIDE_BASE_CONFIG,
            arrows: showArrows,
            autoplay,
            interval: Math.round(autoplayIntervalSec * 1000),
        }).mount();
    });
}

// WordPress loads viewScripts with defer strategy. Deferred scripts run
// after parsing (readyState = 'interactive') but DOMContentLoaded may have
// already fired. Always try immediately since the DOM is ready by the time
// any deferred script executes.
initContentSlider();
