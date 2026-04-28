/**
 * Content Slider carousel
 * Initializes Splide on .content-slider.splide containers for an
 * accessible carousel experience over arbitrary inner blocks.
 */

import Splide from '@splidejs/splide';

export const SPLIDE_CONFIG = {
    type: 'loop',
    perPage: 1,
    pagination: false,
    arrows: true,
    autoplay: false,
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

        new Splide(carousel, SPLIDE_CONFIG).mount();
    });
}

// WordPress loads viewScripts with defer strategy. Deferred scripts run
// after parsing (readyState = 'interactive') but DOMContentLoaded may have
// already fired. Always try immediately since the DOM is ready by the time
// any deferred script executes.
initContentSlider();
