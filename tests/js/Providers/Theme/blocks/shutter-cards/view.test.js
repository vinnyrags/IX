import { describe, it, expect } from 'vitest';
import { activateCard, deactivateAll, initShutterCards } from '../../../../../../src/Providers/Theme/blocks/shutter-cards/view.js';

function createShutterCards(count = 3) {
    const container = document.createElement('div');
    container.classList.add('shutter-cards', 'shutter-cards--preload');

    const cards = [];
    for (let i = 0; i < count; i++) {
        const wrapper = document.createElement('div');
        wrapper.classList.add('wp-block-ix-shutter-card');

        const card = document.createElement('div');
        card.classList.add('shutter-card');

        const toggle = document.createElement('button');
        toggle.classList.add('shutter-card__toggle');
        toggle.setAttribute('aria-label', 'Toggle card');
        // The toggle's aria-expanded is the single source of state.
        toggle.setAttribute('aria-expanded', 'true');

        card.appendChild(toggle);
        wrapper.appendChild(card);
        container.appendChild(wrapper);
        cards.push(wrapper);
    }

    document.body.appendChild(container);
    return { container, cards };
}

function toggleExpanded(card) {
    return card.querySelector('.shutter-card__toggle').getAttribute('aria-expanded');
}

describe('activateCard', () => {
    it('sets toggle aria-expanded true on active card', () => {
        const { cards } = createShutterCards();

        activateCard(cards, cards[1]);

        expect(toggleExpanded(cards[1])).toBe('true');
    });

    it('sets toggle aria-expanded false on inactive cards', () => {
        const { cards } = createShutterCards();

        activateCard(cards, cards[1]);

        expect(toggleExpanded(cards[0])).toBe('false');
        expect(toggleExpanded(cards[2])).toBe('false');
    });

    it('leaves the wrapper free of state attributes', () => {
        const { cards } = createShutterCards();

        activateCard(cards, cards[0]);

        const innerCard = cards[0].querySelector('.shutter-card');
        expect(innerCard.hasAttribute('aria-expanded')).toBe(false);
        expect(innerCard.hasAttribute('data-expanded')).toBe(false);
    });

    it('removes tabindex and role from card wrappers', () => {
        const { cards } = createShutterCards();
        cards[0].setAttribute('tabindex', '0');
        cards[0].setAttribute('role', 'button');

        activateCard(cards, cards[0]);

        expect(cards[0].hasAttribute('tabindex')).toBe(false);
        expect(cards[0].hasAttribute('role')).toBe(false);
    });

    it('updates toggle aria-label', () => {
        const { cards } = createShutterCards();

        activateCard(cards, cards[0]);

        expect(cards[0].querySelector('.shutter-card__toggle').getAttribute('aria-label')).toBe('Collapse card');
        expect(cards[1].querySelector('.shutter-card__toggle').getAttribute('aria-label')).toBe('Expand card');
    });
});

describe('deactivateAll', () => {
    it('sets every toggle aria-expanded to false', () => {
        const { cards } = createShutterCards();

        deactivateAll(cards);

        cards.forEach((card) => {
            expect(toggleExpanded(card)).toBe('false');
        });
    });

    it('sets all toggle labels to Expand card', () => {
        const { cards } = createShutterCards();

        deactivateAll(cards);

        cards.forEach((card) => {
            expect(card.querySelector('.shutter-card__toggle').getAttribute('aria-label')).toBe('Expand card');
        });
    });
});

describe('initShutterCards', () => {
    it('activates first card on init', () => {
        const { cards } = createShutterCards();

        initShutterCards();

        expect(toggleExpanded(cards[0])).toBe('true');
        expect(toggleExpanded(cards[1])).toBe('false');
    });

    it('removes preload class', () => {
        const { container } = createShutterCards();

        initShutterCards();

        expect(container.classList.contains('shutter-cards--preload')).toBe(false);
    });

    it('activates inactive card on click', () => {
        const { cards } = createShutterCards();

        initShutterCards();
        cards[1].click();

        expect(toggleExpanded(cards[1])).toBe('true');
        expect(toggleExpanded(cards[0])).toBe('false');
    });

    it('skips container with fewer than 2 cards', () => {
        const { container } = createShutterCards(1);

        initShutterCards();

        expect(container.classList.contains('shutter-cards--preload')).toBe(true);
    });

    it('toggle on active card deactivates all', () => {
        const { cards } = createShutterCards();

        initShutterCards();
        const toggle = cards[0].querySelector('.shutter-card__toggle');
        toggle.click();

        cards.forEach((card) => {
            expect(toggleExpanded(card)).toBe('false');
        });
    });

    it('toggle on inactive card activates it', () => {
        const { cards } = createShutterCards();

        initShutterCards();
        const toggle = cards[1].querySelector('.shutter-card__toggle');
        toggle.click();

        expect(toggleExpanded(cards[1])).toBe('true');
        expect(toggleExpanded(cards[0])).toBe('false');
    });
});
