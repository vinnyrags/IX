/**
 * Shutter cards interactive behavior
 * Manages card activation, ARIA state, and keyboard navigation.
 *
 * The toggle button's aria-expanded is the single source of truth for
 * card state. CSS reads it via :has(); JS reads it via isExpanded().
 * The wrapper carries no state attribute and no interactive role — it's
 * a mouse hit-target only, not part of the accessibility tree.
 */

/**
 * Read the canonical expanded state from the toggle button.
 * @param {HTMLElement} card - The .wp-block-ix-shutter-card wrapper
 * @returns {boolean}
 */
function isExpanded(card) {
    return card.querySelector('.shutter-card__toggle')?.getAttribute('aria-expanded') === 'true';
}

/**
 * Activate a card and deactivate others
 * @param {NodeList|HTMLElement[]} cards - All cards in the container
 * @param {HTMLElement} cardToActivate - The card to activate
 */
export function activateCard(cards, cardToActivate) {
    cards.forEach((card) => {
        const isActive = card === cardToActivate;

        // Wrapper stays out of the a11y tree — no role, no tabindex, no
        // state attribute. Mouse-only hit target.
        card.removeAttribute('tabindex');
        card.removeAttribute('role');

        const toggle = card.querySelector('.shutter-card__toggle');
        if (toggle) {
            toggle.setAttribute('aria-label', isActive ? 'Collapse card' : 'Expand card');
            toggle.setAttribute('aria-expanded', isActive ? 'true' : 'false');
        }
    });
}

/**
 * Deactivate all cards (no card is active)
 * @param {NodeList|HTMLElement[]} cards - All cards in the container
 */
export function deactivateAll(cards) {
    cards.forEach((card) => {
        card.removeAttribute('tabindex');
        card.removeAttribute('role');

        const toggle = card.querySelector('.shutter-card__toggle');
        if (toggle) {
            toggle.setAttribute('aria-label', 'Expand card');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}

/**
 * Initialize shutter cards for all containers on the page
 */
export function initShutterCards() {
    const containers = document.querySelectorAll('.shutter-cards');

    containers.forEach((container) => {
        const cards = container.querySelectorAll('.wp-block-ix-shutter-card');

        if (cards.length < 2) return;

        function handleActivation(card) {
            if (!isExpanded(card)) {
                activateCard(cards, card);
            }
        }

        // Set initial state
        activateCard(cards, cards[0]);

        // Remove preload class
        container.classList.remove('shutter-cards--preload');

        // Event listeners for each card
        cards.forEach((card) => {
            // Click anywhere on the card surface activates it (mouse only).
            // The toggle button stops propagation, so its own click below
            // handles the active→collapse path without double-firing.
            card.addEventListener('click', function (e) {
                if (isExpanded(this)) return;
                if (e.target.closest('.shutter-card__toggle')) return;
                handleActivation(this);
            });

            const toggle = card.querySelector('.shutter-card__toggle');
            if (toggle) {
                toggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (isExpanded(card)) {
                        deactivateAll(cards);
                        toggle.focus();
                    } else {
                        handleActivation(card);
                    }
                });
            }
        });
    });
}

// Auto-init when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initShutterCards);
} else {
    initShutterCards();
}
