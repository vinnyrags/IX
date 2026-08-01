import { describe, it, expect, vi, beforeEach } from 'vitest';

// Mock Splide — factory must not reference outer variables (hoisted)
vi.mock('@splidejs/splide', () => {
    const mount = vi.fn();
    const Splide = vi.fn(() => ({ mount }));
    return { default: Splide, __mount: mount };
});

import { default as Splide, __mount as mountMock } from '@splidejs/splide';
import {
    SPLIDE_BASE_CONFIG,
    initContentSlider,
} from '../../../../../../src/Providers/Theme/blocks/content-slider/view.js';

/**
 * Build a content-slider carousel with N slides
 */
function createContentSlider(count = 3) {
    const carousel = document.createElement('div');
    carousel.classList.add('content-slider', 'splide');
    carousel.setAttribute('aria-label', 'Content slider');

    const track = document.createElement('div');
    track.classList.add('splide__track');

    const list = document.createElement('ul');
    list.classList.add('splide__list');

    for (let i = 0; i < count; i++) {
        const slide = document.createElement('li');
        slide.classList.add('splide__slide');

        const quote = document.createElement('blockquote');
        quote.classList.add('wp-block-quote');
        quote.innerHTML = `<p>Slide ${i + 1}</p><cite>Author ${i + 1}</cite>`;

        slide.appendChild(quote);
        list.appendChild(slide);
    }

    track.appendChild(list);
    carousel.appendChild(track);
    document.body.appendChild(carousel);
    return carousel;
}

beforeEach(() => {
    Splide.mockClear();
    mountMock.mockClear();
});

describe('SPLIDE_BASE_CONFIG', () => {
    it('sets type to loop', () => {
        expect(SPLIDE_BASE_CONFIG.type).toBe('loop');
    });

    it('shows one slide at a time', () => {
        expect(SPLIDE_BASE_CONFIG.perPage).toBe(1);
    });

    it('disables pagination', () => {
        expect(SPLIDE_BASE_CONFIG.pagination).toBe(false);
    });

    it('has accessible i18n labels', () => {
        expect(SPLIDE_BASE_CONFIG.i18n.prev).toBe('Previous slide');
        expect(SPLIDE_BASE_CONFIG.i18n.next).toBe('Next slide');
        expect(SPLIDE_BASE_CONFIG.i18n.slideX).toBe('Go to slide %s');
        expect(SPLIDE_BASE_CONFIG.i18n.pageX).toBe('Go to page %s');
    });
});

describe('initContentSlider', () => {
    it('initializes Splide with the base config plus per-block defaults', () => {
        const carousel = createContentSlider(3);

        initContentSlider();

        // arrows/autoplay default to true (block.json defaults) and the
        // autoplay interval defaults to 5s when no data-* overrides are present.
        expect(Splide).toHaveBeenCalledWith(carousel, {
            ...SPLIDE_BASE_CONFIG,
            arrows: true,
            autoplay: true,
            interval: 5000,
        });
        expect(mountMock).toHaveBeenCalled();
    });

    it('honors per-block data attributes to toggle arrows and autoplay', () => {
        const wrapper = document.createElement('div');
        wrapper.classList.add('wp-block-ix-content-slider');
        wrapper.setAttribute('data-arrows', 'false');
        wrapper.setAttribute('data-autoplay', 'false');
        document.body.appendChild(wrapper);

        const carousel = createContentSlider(3);
        wrapper.appendChild(carousel); // reparent under the block wrapper

        initContentSlider();

        expect(Splide).toHaveBeenCalledWith(
            carousel,
            expect.objectContaining({ arrows: false, autoplay: false })
        );
    });

    it('skips Splide initialization for fewer than 2 slides but marks the container is-initialized so visibility lifts', () => {
        const carousel = createContentSlider(1);

        initContentSlider();

        expect(Splide).not.toHaveBeenCalled();
        expect(mountMock).not.toHaveBeenCalled();
        expect(carousel.classList.contains('is-initialized')).toBe(true);
    });

    it('initializes multiple carousels independently', () => {
        createContentSlider(3);
        createContentSlider(2);

        initContentSlider();

        expect(Splide).toHaveBeenCalledTimes(2);
        expect(mountMock).toHaveBeenCalledTimes(2);
    });

    it('initializes carousel with exactly 2 slides', () => {
        createContentSlider(2);

        initContentSlider();

        expect(Splide).toHaveBeenCalledTimes(1);
        expect(mountMock).toHaveBeenCalledTimes(1);
    });
});
