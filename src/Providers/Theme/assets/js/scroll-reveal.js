// ==========================================================================
// Scroll Reveal — frontend
//
// Tags matched elements with `.fade-up`, observes them via
// IntersectionObserver, and adds `.is-visible` once they cross the
// viewport threshold. The matching CSS lives in features/scroll-reveal.scss
// (also enqueued by the IX ScrollReveal feature).
//
// Config is passed in via wp_localize_script as window.ixScrollReveal:
//   - selectors:        string[] — CSS selectors to animate
//   - excludeAncestors: string[] — ancestor selectors that disqualify a match
//   - staggerDelay:     number   — seconds between sibling reveals
//
// Defaults below are used only if the global is missing (e.g. the script
// is loaded outside the IX feature flow).
// ==========================================================================

(function () {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) return;

    const config = window.ixScrollReveal || {};
    const selectors = Array.isArray(config.selectors) && config.selectors.length
        ? config.selectors
        : ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    const excludeAncestors = Array.isArray(config.excludeAncestors) ? config.excludeAncestors : [];
    const staggerDelay = typeof config.staggerDelay === 'number' ? config.staggerDelay : 0.1;

    const selectorList = selectors.join(',');
    const elements = [...document.querySelectorAll(selectorList)]
        .filter((el) => !excludeAncestors.some((sel) => el.closest(sel)));
    if (!elements.length) return;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -50px 0px' }
    );

    // Track stagger index per parent container so siblings cascade in
    // reading order rather than each animating independently.
    const parentCounters = new Map();

    elements.forEach((el) => {
        const rect = el.getBoundingClientRect();
        const inViewport = rect.top < window.innerHeight && rect.bottom > 0;

        el.classList.add('fade-up');

        const parent = el.parentElement;
        if (parent) {
            const index = parentCounters.get(parent) || 0;
            if (index > 0) {
                el.style.transitionDelay = `${index * staggerDelay}s`;
            }
            parentCounters.set(parent, index + 1);
        }

        if (inViewport) {
            el.classList.add('is-visible');
        } else {
            observer.observe(el);
        }
    });

    // The -50px bottom rootMargin means elements at the very end of the
    // document can never intersect — the user can't scroll them 50px
    // above the viewport bottom. Reveal anything still pending when the
    // page hits its bottom.
    const revealRemaining = () => {
        const atBottom =
            window.innerHeight + window.scrollY >=
            document.documentElement.scrollHeight - 50;
        if (atBottom) {
            document.querySelectorAll('.fade-up:not(.is-visible)').forEach((el) => {
                el.classList.add('is-visible');
                observer.unobserve(el);
            });
            window.removeEventListener('scroll', revealRemaining);
        }
    };
    window.addEventListener('scroll', revealRemaining, { passive: true });
})();
