/* Site-wide helpers.
   1. Measures the sticky header and stores its height in --sgh-header-h, so the sticky sidebar and the
      calculator result box never sit under the header or run past the bottom of the screen.
   2. Home page only: sections fade up gently as they scroll into view. */
(function () {
    'use strict';

    var header = document.querySelector('.sgh-header');
    if (!header) { return; }

    function update() {
        var height = Math.round(header.getBoundingClientRect().height);
        document.documentElement.style.setProperty('--sgh-header-h', height + 'px');
    }

    update();
    window.addEventListener('resize', update);
    window.addEventListener('load', update);

    // The header grows when the search bar opens, and shrinks when it closes
    if ('ResizeObserver' in window) {
        new ResizeObserver(update).observe(header);
    }
})();

(function () {
    'use strict';

    // Only on the home page (it has the hero banner), and not for people who prefer less motion
    if (!document.querySelector('.sgh-banner') || !('IntersectionObserver' in window)) { return; }
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }

    var items = document.querySelectorAll('.sgh-section, .sgh-home-tools');
    document.documentElement.classList.add('js-reveal');

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });

    items.forEach(function (el) {
        el.classList.add('sgh-reveal');
        observer.observe(el);
    });
})();