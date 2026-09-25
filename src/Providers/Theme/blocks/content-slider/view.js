/**
 * Content Slider carousel
 * Initializes Splide on .content-slider.splide containers for an
 * accessible carousel experience over arbitrary inner blocks.
 *
 * Per-block toggles (arrows, autoplay) come from data-arrows /
 * data-autoplay on the block wrapper (set by render.php from the
 * showArrows / autoplay attributes). Defaults match block.json
 * (both true).
 *
 * A theme can contribute arbitrary Splide options through the
 * `ix/content_slider/splide_config` PHP filter; render.php serialises them
 * onto data-splide and they are merged last here, so a filter can override
 * anything this file sets. That is the extension point for per-site
 * behaviour — a three-up press carousel, autoWidth, a custom gap — and it
 * exists so a child theme never has to replace this whole script to reach
 * Splide's options. Unfiltered sliders carry no data-splide at all and
 * behave exactly as they always have.
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
 * Extra Splide options contributed by a theme via the
 * `ix/content_slider/splide_config` PHP filter, carried on data-splide as JSON.
 *
 * Absent on every slider nobody has filtered, which is the common case. Parsed
 * defensively: a malformed blob degrades to the default carousel rather than
 * throwing and leaving the track invisible (Splide's stylesheet hides .splide
 * until .is-initialized lands, so an exception here would blank the section).
 */
function readSplideConfig(carousel) {
    const raw = getWrapper(carousel)?.getAttribute('data-splide');
    if (!raw) return {};

    try {
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
    } catch {
        return {};
    }
}

/**
 * Initialize content slider carousels on the page.
 * Skips initialization if fewer than 2 slides.
 */
export function initContentSlider() {
    const carousels = document.querySelectorAll('.content-slider.splide:not(.is-initialized)');

    carousels.forEach((carousel) => {
        const slides = carousel.querySelectorAll('.splide__slide');
        const themeConfig = readSplideConfig(carousel);

        // How many slides are on screen at once — 1 unless a theme filter said
        // otherwise. The skip threshold below depends on it.
        const perPage = Number.isFinite(themeConfig.perPage) && themeConfig.perPage > 0
            ? themeConfig.perPage
            : SPLIDE_BASE_CONFIG.perPage;

        // Splide's stylesheet sets visibility:hidden on .splide and only lifts
        // it when .is-initialized is present. We skip Splide when there is
        // nothing to page through, so add the class manually — the slides
        // render as static content but stay visible.
        //
        // The threshold scales with perPage rather than being a flat 2: a 3-up
        // carousel holding exactly 3 slides has nothing to advance to, and
        // mounting it anyway produces live arrows that go nowhere.
        if (slides.length <= perPage) {
            carousel.classList.add('is-initialized');
            return;
        }

        const showArrows       = readBoolAttr(carousel, 'data-arrows', true);
        const autoplay         = readBoolAttr(carousel, 'data-autoplay', true);
        const autoplayIntervalSec = readNumberAttr(carousel, 'data-autoplay-interval', 5);
        const isFade           = getWrapper(carousel)?.getAttribute('data-transition') === 'fade';

        new Splide(carousel, {
            ...SPLIDE_BASE_CONFIG,
            // Splide's fade type crossfades slides in place; it can't loop, so it
            // pairs with rewind. Default ('slide') keeps the looping carousel.
            ...(isFade ? { type: 'fade', rewind: true } : {}),
            arrows: showArrows,
            autoplay,
            interval: Math.round(autoplayIntervalSec * 1000),
            // Theme config last so a filter can override anything above it,
            // including the block's own attribute-derived values.
            ...themeConfig,
        }).mount();
    });
}

// WordPress loads viewScripts with defer strategy. Deferred scripts run
// after parsing (readyState = 'interactive') but DOMContentLoaded may have
// already fired. Always try immediately since the DOM is ready by the time
// any deferred script executes.
initContentSlider();
